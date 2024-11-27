<?php
declare(strict_types=1);

namespace App\Application\Mattermost;

use App\Application\Mattermost\Response\Response;
use Symfony\Component\Cache\Adapter\RedisAdapter;


final class Connector {
    const BOT_USER_ID = 'cai3rqeh57895xha3i6ainmawa';

    private \CurlHandle $curl;
    private string $tokenId;
    private string $accessToken;
    private string $baseUrl;
    private \Redis $cache;

    public function __construct(
        string $tokenId,
        string $accessToken,
        string $baseUrl,
        string $redisUrl
    ) {
        $this->tokenId = $tokenId;
        $this->accessToken = $accessToken;
        $this->baseUrl = $baseUrl;

        $this->cache = RedisAdapter::createConnection($redisUrl);
        $this->curl = curl_init();
    }

    public function __destruct() {
        curl_close($this->curl);
    }

    public function sendMessageToChannel(ChatMessage $message): Response
    {
        $this->createOptions('/posts', 'POST', $message->getMessage());

        return $this->createResponse(curl_exec($this->curl));
    }

    public function sendMessageToUser(ChatMessage $message): Response
    {
        $this->createOptions('/channels/direct', 'POST', json_encode([self::BOT_USER_ID, $message->channel_id]));
        $response1 = curl_exec($this->curl);
        $json = json_decode($response1, true);

        if (in_array('status_code', $json) && $json['status_code'] !== 200)
            return Response::createErrorResponse();

        $message->channel_id = $json['id'];
        $this->createOptions('/posts', 'POST', $message->getMessage());

        return $this->createResponse(curl_exec($this->curl));
    }

    public function getChannelMessages(string $channelId, int $count = 100): Response
    {
        $cacheResult = $this->cache->get(sprintf('channel_messages_%s', $channelId));
        if ($cacheResult === false) {
            $this->createOptions('/channels/' . $channelId . '/posts?page=0&per_page=' . $count, 'GET');

            $result = curl_exec($this->curl);
            if ($result !== false) {
                $this->cache->set(sprintf('channel_messages_%s', $channelId), $result, Period::MINUTE->getTTL());
            }
        } else {
            $result = $cacheResult;
        }

        return $this->createResponse($result);
    }

    public function reactToMessage(string $messageId, string $emoji, bool $add = true): Response
    {
        if ($add) {
            $options = [
                'user_id' => self::BOT_USER_ID,
                'post_id' => $messageId,
                'emoji_name' => $emoji,
            ];
            $this->createOptions('/reactions', 'POST', json_encode($options));
        } else {
            $this->createOptions('/users/' . self::BOT_USER_ID . '/posts/' . $messageId . '/reactions/' . $emoji , 'DELETE');
        }

        return $this->createResponse(curl_exec($this->curl));
    }

    public function getUserInfo(string $username, bool $ignoreCache = false): Response
    {
        $cacheResult = $this->cache->get(sprintf('username_%s', $username));
        if ($cacheResult === false || $ignoreCache) {
            $options = [
                $username
            ];
            $this->createOptions('/users/usernames', 'POST', json_encode($options));

            $response = curl_exec($this->curl);
            if ($response === false)
                return Response::createErrorResponse();

            $json = json_decode($response, true);
            if (count($json) === 0 || array_key_exists('detailed_error', $json))
                return Response::createErrorResponse();

            $resultUser = Response::createUser($json[0]);

            $this->cache->set(sprintf('username_%s', $username), $response, Period::HOUR->getTTL());

            return $resultUser;
        } else {
            $json = json_decode($cacheResult, true);
            return Response::createUser($json[0]);
        }
    }

    public function getUsers(int $page): Response
    {
        $this->createOptions(sprintf('/users?page=%d&per_page=400', $page-1), 'GET');

        $response = curl_exec($this->curl);
        $json = json_decode($response, true);

        if (in_array('status_code', $json) && $json['status_code'] !== 200)
            return Response::createErrorResponse();

        return Response::createUsers($response);
    }

    public function getChannelUsers(string $channelId, int $page): Response
    {
        $cacheResult = $this->cache->get(sprintf('channel_users_%s_%d', $channelId, $page));
        if ($cacheResult === false) {
            $this->createOptions(sprintf('/users?page=%d&per_page=200&in_channel=%s&active=true&sort=status', $page -1, $channelId), 'GET');

            $response = curl_exec($this->curl);
            $json = json_decode($response, true);

            if (in_array('status_code', $json) && $json['status_code'] !== 200)
                return Response::createErrorResponse();

            if ($response !== false) {
                $this->cache->set(sprintf('channel_users_%s_%d', $channelId, $page), $response, Period::MINUTE->getTTL(10));
            }

            return Response::createUsers($response);
        } else {
            return Response::createUsers($cacheResult);
        }
    }

    public function getUserStatus(string $userId): string
    {
        $cacheResult = $this->cache->get(sprintf('user_status_%s', $userId));
        if ($cacheResult === false) {

            $this->createOptions('/users/' . $userId . '/status', 'GET');

            $response =curl_exec($this->curl);
            $json = json_decode($response, true);

            if ($response !== false) {
                $this->cache->set(sprintf('user_status_%s', $userId), $json['status'], Period::MINUTE->getTTL(10));
            }

            return $json['status'];
        } else {
            return $cacheResult;
        }
    }

    public function deactivateUser(string $userId, string $username): Response
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://mmoauth2.imagefap.com/api/ban');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, sprintf('{"username":"%s","expires":"%s"}', $username, '2030-12-31 23:59:59'));

        $headers = array();
        $headers[] = 'X-Api-Key: a8fedc95b23b7a6f4c846a17902667f01c6887167514726bbf0f1b6accec5d56';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        curl_close($ch);

        $this->createOptions('/users/' . $userId, 'DELETE');

        $this->cache->del([
            sprintf('username_%s', $username),
        ]);

        $newResult = curl_exec($this->curl);
        $newResult = json_decode($newResult, true);
        $newResult['request'] = $result;

        return $this->createResponse(json_encode($newResult));
    }

    public function activateUser(string $userId, string $username): Response
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://mmoauth2.imagefap.com/api/ban');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, sprintf('{"username":"%s","expires":"%s"}', $username, '2023-03-10 23:59:59'));

        $headers = array();
        $headers[] = 'X-Api-Key: a8fedc95b23b7a6f4c846a17902667f01c6887167514726bbf0f1b6accec5d56';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        curl_close($ch);

        $this->createOptions('/users/' . $userId . '/active', 'PUT', json_encode([
            'active' => true
        ]));

        $this->cache->del([
            sprintf('username_%s', $username),
        ]);

        return $this->createResponse(curl_exec($this->curl));
    }

    public function deleteMessage(string $messageId): Response
    {
        $this->createOptions('/posts/' . $messageId, 'DELETE');
        $response = curl_exec($this->curl);

        return $this->createResponse($response);
    }

    public function autoCompleteUsername(string $partial): Response
    {
        $cacheResult = $this->cache->get(sprintf('autocomplete_%s', $partial));
        if ($cacheResult !== false) {
            return $this->createResponse($cacheResult);
        }

        $this->createOptions('/users/autocomplete?limit=10&name=' . $partial, 'GET');

        $response = curl_exec($this->curl);
        if ($response !== false) {
            $this->cache->set(sprintf('autocomplete_%s', $partial), $response, Period::MINUTE->getTTL(15));
        }

        return $this->createResponse($response);
    }

    public function getPublicLink(string $fileId): Response
    {
        $cacheResult = $this->cache->get(sprintf('file_%s', $fileId));
        if ($cacheResult !== false) {
            return $this->createResponse($cacheResult);
        }

        $this->createOptions('/files/' . $fileId . '/link', 'GET');

        $response = curl_exec($this->curl);
        if ($response !== false) {
            $this->cache->set(sprintf('file_%s', $fileId), $response, Period::DAY->getTTL(2));
        }

        return $this->createResponse($response);
    }

    private function createResponse(string|bool $json, string $request = null): Response
    {
        if ($json === false)
            return Response::createErrorResponse();

        return new Response($json, false, $request);
    }

    private function createOptions(string $endpoint, string $method, string $body = null): array
    {
        $options = [
            CURLOPT_URL => $this->baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content: application/json',
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json'
            ]
        ];

        if($body) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($this->curl, $options);

        return $options;
    }
}
