<?php
declare(strict_types=1);

namespace App\Application\RocketChat;

use App\Application\RocketChat\Response\Response;
use DateTimeImmutable;
use DateTimeZone;

final class Connector {
    public const CONTENT_TYPE_JSON = 'application/json';
    public const CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';

    private \CurlHandle $curl;
    private string $authToken;
    private string $userId;
    private string $baseUrl;

    public function __construct(
        string $authToken,
        string $userId,
        string $baseUrl
    ) {
        $this->authToken = $authToken;
        $this->userId = $userId;
        $this->baseUrl = $baseUrl;

        $this->curl = curl_init();
        $this->login();
    }

    public function __destruct() {
        curl_close($this->curl);
    }

    public function runCommand(string $paramaters): ?Response
    {
        $this->createOptions('commands.run', 'POST', self::CONTENT_TYPE_FORM, $paramaters);
        $response = curl_exec($this->curl);

        if($response === false)
            return null;

        return new Response($response);
    }

    public function postMessage(string $message): ?Response
    {
        $this->createOptions('chat.postMessage', 'POST', self::CONTENT_TYPE_JSON, $message);

        $response = curl_exec($this->curl);

        if($response === false)
            return null;

        return new Response($response);
    }

    public function getChannelMessages(string $roomId, int $count = 100): ?Response
    {
        $this->createOptions(sprintf('channels.messages?roomId=%s&count=%s', $roomId, $count), 'GET');

        $response = curl_exec($this->curl);

        if($response === false)
            return null;

        return new Response($response);
    }

    public function getGroupMessages(string $roomId, ?DateTimeImmutable $start = null, ?DateTimeImmutable $end = null): ?Response
    {
        $end = $end ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $start = $start ?? $end->modify('-1 hour');

        $endpoint = sprintf(
            'groups.history?roomId=%s&latest=%s&oldest=%s', 
            $roomId, 
            $end->format('Y-m-d') . 'T' . $end->format('H:i:s') . '.000Z',
            $start->format('Y-m-d') . 'T' . $start->format('H:i:s') . '.000Z'
        );

        $this->createOptions($endpoint, 'GET');

        $response = curl_exec($this->curl);

        if($response === false)
            return null;

        return new Response($response);
    }

    public function reactToMessage(string $messageId, string $emoji, bool $add): ?Response
    {
        $message = [
            'messageId' => $messageId,
            'emoji' => $emoji,
            'shouldReact' => $add
        ];
        $jsonMessage = json_encode((object)$message);

        $this->createOptions('chat.react', 'POST', self::CONTENT_TYPE_JSON, $jsonMessage);

        $response = curl_exec($this->curl);

        if($response === false)
            return null;

        return new Response($response);
    }

    public function getUserInfo(string $username): ?Response
    {
        $this->createOptions(sprintf('users.info?username=%s', $username), 'GET');

        $response = curl_exec($this->curl);

        if($response === false)
            return null;

        return new Response($response);
    }

    public function getUsers(string $status, int $page): ?Response
    {
        $pageSize = 100;
        $offset = ($page - 1) * 100;
        $queryString = sprintf('?query={"status":"%s","active":true}&count=%d&offset=%d', $status, $pageSize, $offset);
        $this->createOptions('users.list', 'GET', null, $queryString);

        $response = curl_exec($this->curl);

        if($response === false)
            Response::createErrorResponse();

        return new Response($response);
    }

    public function getDeactivatedUsers(int $page): ?Response
    {
        $pageSize = 100;
        $offset = ($page - 1) * 100;
        $queryString = sprintf('?query={"active":false}&count=%d&offset=%d', $pageSize, $offset);
        $this->createOptions('users.list', 'GET', null, $queryString);

        $response = curl_exec($this->curl);

        if($response === false)
            Response::createErrorResponse();

        return new Response($response);
    }

    public function updateUser(string $userId, bool $active): Response
    {
        $this->createOptions('users.update', 'POST', self::CONTENT_TYPE_JSON, json_encode([
            'userId' => $userId,
            'data' => [
                'active' => $active
            ]
        ]));

        $response = curl_exec($this->curl);

        return new Response($response);
    }

    public function fixUserProfile(string $userId, string $username, string $gender): Response
    {
        $this->createOptions('users.update', 'POST', self::CONTENT_TYPE_JSON, json_encode([
            'userId' => $userId,
            'data' => [
                'customFields' => [
                    'Profile' => sprintf('https://imagefap.com/profile/%s', $username),
                    'Gender' => $gender
                ]
            ]
        ]));

        $response = curl_exec($this->curl) ;

        return new Response($response);
    }

    public function getReportsByUser(?DateTimeImmutable $start = null, ?DateTimeImmutable $end = null): ?Response
    {
        $end = $end ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $start = $start ?? $end->modify('-1 hour');

        $endpoint = sprintf(
            'moderation.reportsByUsers?latest=%s&oldest=%s',
            $end->format('Y-m-d') . 'T' . $end->format('H:i:s') . '.000Z',
            $start->format('Y-m-d') . 'T' . $start->format('H:i:s') . '.000Z'
        );

        $this->createOptions($endpoint, 'GET');

        $response = curl_exec($this->curl);

        if($response === false)
            return null;

        return new Response($response);
    }

    public function getReportsForUser(string $userId): ?Response
    {
        $endpoint = sprintf(
            'moderation.user.reportedMessages?userId=%s',
            $userId
        );

        $this->createOptions($endpoint, 'GET');

        $response = curl_exec($this->curl);

        if($response === false)
            return null;

        return new Response($response);
    }

    public function getReportsForMessage(string $messageId): ?Response
    {
        $endpoint = sprintf(
            'moderation.reports?msgId=%s',
            $messageId
        );

        $this->createOptions($endpoint, 'GET');

        $response = curl_exec($this->curl);

        if($response === false)
            return null;

        return new Response($response);
    }

    public function dismissReports(string $userId): ?Response
    {
        $this->createOptions('moderation.dismissReports', 'POST', self::CONTENT_TYPE_FORM, 'userId=' . $userId);

        try {
            $response = curl_exec($this->curl);
        } catch (\Exception $e) {
            return Response::createErrorResponse();
        }

        if($response === false)
            return Response::createErrorResponse();

        return new Response($response);
    }

    public function deletedUserReportedMessages(string $userId): ?Response
    {
        $this->createOptions('moderation.user.deleteReportedMessages', 'POST', self::CONTENT_TYPE_FORM, 'userId=' . $userId);

        $response = curl_exec($this->curl);

        if($response === false)
            return Response::createErrorResponse();

        return new Response($response);
    }

    public function deleteMessage(string $messageId, string $channelId): ?Response
    {
        $this->createOptions('chat.delete', 'POST', self::CONTENT_TYPE_FORM, sprintf('msgId=%s&roomId=%s', $messageId, $channelId));

        $response = curl_exec($this->curl);

        if($response === false)
            return Response::createErrorResponse();

        return new Response($response);
    }

    public function autoCompleteUsername(string $partial): ?Response
    {
        $body = [
            'term' => $partial
        ];
        $this->createOptions('users.autocomplete', 'GET', self::CONTENT_TYPE_FORM, sprintf('?selector=%s', json_encode($body)));

        $response = curl_exec($this->curl);

        if($response === false)
            return null;

        return new Response($response);
    }

    private function createHeaders(string $contentType = null): array
    {
        $headers = [
            sprintf('X-User-ID: %s', $this->userId),
            sprintf('X-Auth-Token: %s', $this->authToken),
            'Content: application/json'
        ];

        if($contentType)
            $headers[] = sprintf('Content-Type: %s', $contentType);

        return $headers;
    }

    private function createOptions(string $endpoint, string $method, string $contentType = null, string $body = null): array
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
            CURLOPT_HTTPHEADER => $this->createHeaders($contentType)
        ];

        if($body) {
            if ($method === 'GET')
                $options[CURLOPT_URL] .= $body;
            else
                $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($this->curl, $options);    

        return $options;
    }

    private function login(): void
    {
        curl_setopt_array($this->curl, array(
            CURLOPT_URL => $this->baseUrl . 'login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => sprintf('{
                "resume": %s
            }', $this->authToken),
            CURLOPT_HTTPHEADER => array(
                sprintf('X-User-ID: %s', $this->userId),
                'Content-Type: application/json'
            ),
        ));

        curl_exec($this->curl);
    }
}
