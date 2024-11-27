<?php
declare(strict_types=1);

namespace App\Controller;

use App\Application\Chat\IntegrationInterface;
use App\Application\Mattermost\MatterMostIntegration;
use App\Application\RocketChat\RocketChatIntegration;
use App\Entity\ApiLog;
use App\Entity\Mute;
use App\Entity\Repository\ApiLogRepository;
use App\Entity\Repository\ChannelRepository;
use App\Entity\Repository\MuteRepository;
use App\Entity\Repository\UserRepository;
use App\Entity\Repository\WarningRepository;
use App\Entity\User;
use App\Entity\Warning;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


final class WebhookController
{
    private const COMMANDS = [
        'mute7',
        'mute3',
        'test',
        'deactivate',
        'commands',
        'warning',
        'online'
    ];

    private ApiLogRepository $apiLogRepository;
    private ChannelRepository $channelRepository;
    private UserRepository $userRepository;
    private IntegrationInterface $integration;
    private MuteRepository $muteRepository;
    private WarningRepository $warningRepository;

    public function __construct(
        ApiLogRepository $apiLogRepository,
        ChannelRepository $channelRepository,
        UserRepository $userRepository,
        IntegrationInterface $integration,
        MuteRepository $muteRepository,
        WarningRepository $warningRepository
    ) {
        $this->apiLogRepository = $apiLogRepository;
        $this->channelRepository = $channelRepository;
        $this->userRepository = $userRepository;
        $this->integration = $integration;
        $this->muteRepository = $muteRepository;
        $this->warningRepository = $warningRepository;
    }

    public function __invoke(Request $request, ?string $command = null): Response
    {
        if ($this->integration instanceof RocketChatIntegration) {
            $message = json_decode($request->getContent(), true);
            $cleanedInput = preg_replace('/\s+/', ' ', trim(str_replace($message['trigger_word'], '', $message['text'])));

            $command = $command ?? str_replace('!', '', $message['trigger_word']);

            // Make sure the command exists and the user is not fapbot
            if ($message['user_name'] === 'fapbot' || !in_array($command, self::COMMANDS))
                return JsonResponse::fromJsonString('{}', 200);

            // If the user doesn't have an account in fapbot they're not allowed to use fapbot
            $user = $this->userRepository->findByUsername($message['user_name']);
            if ($user === null)
                return JsonResponse::fromJsonString('{}', 200);

            $channelId = array_key_exists('channel_id', $message) ? $message['channel_id'] : '';

            return match ($command) {
                'mute7' => $this->handleMute($cleanedInput, $user, 7, $channelId),
                'mute3' => $this->handleMute($cleanedInput, $user, 3, $channelId),
                'test' => $this->handleTest($cleanedInput, $user, $channelId),
                'deactivate' => $this->deactivate($user, $cleanedInput),
                'commands' => $this->showCommands($user),
                'warning' => $this->handleWarning($cleanedInput, $user),
                default => JsonResponse::fromJsonString(json_encode([
                    'text' => "Tell @MyxR that he's been a dummy and forgot to add this command"
                ])),
            };
        }

        if ($this->integration instanceof MatterMostIntegration) {
            $text = $request->get('text');

            // Make sure the command exists and the user is not fapbot
            if ($request->get('user_name') === 'fapbot' || !in_array($command, self::COMMANDS))
                return JsonResponse::fromJsonString('{}', 200);

            $user = $this->userRepository->findByUsername($request->get('user_name'));
            if ($user === null && $command !== 'online')
                return JsonResponse::fromJsonString('{}', 200);

            $channelId = $request->get('channel_id');

            return match ($command) {
                'mute7' => $this->handleMute($text, $user, 7, $channelId, true),
                'mute3' => $this->handleMute($text, $user, 3, $channelId, true),
                'test' => $this->handleTest($text, $user, $channelId),
                'deactivate' => $this->deactivate($user, $text),
                'commands' => $this->showMatterMostCommands($user),
                'warning' => $this->handleWarning($text, $user),
                'online' => $this->handleUserList($channelId),
                default => JsonResponse::fromJsonString(json_encode([
                    'text' => "Tell @MyxR that he's been a dummy and forgot to add this command"
                ])),
            };
        }

        return new JsonResponse('Not implemented', 501);
    }

    private function handleMute(string $command, User $muter, int $duration, ?string $channelId, bool $informChannel = false): JsonResponse
    {
        try {
            $parts = explode(' ', trim($command));
            $usernames = array_filter($parts, fn($part) => $part[0] === '@');
            $channels = array_filter($parts, fn($part) => $part[0] === '#');

            if (count($channels) === 0 && $channelId !== '' && $channelId !== null) {
                $channel = $this->channelRepository->findByIdentifier($channelId);

                if ($channel !== null)
                    $channels[] = '#'. $channel->getName();
            }

            if (count($usernames) === 0 || count($channels) === 0)
                return JsonResponse::fromJsonString(json_encode([
                    'text' => "Username and channel are both required"
                ]));
        } catch (\Exception $e) {
            return JsonResponse::fromJsonString(json_encode([
                'text' => "Couldn't parse input"
            ]));
        }

        try {
            $channelEntities = array_map(fn ($channel) => $this->channelRepository->findByName(str_replace('#', '', $channel)), $channels);
        } catch (\Exception $e) {
            return $this->returnText("Couldn't find the channel");
        }

        foreach ($channelEntities as $channel) {
            if ($muter->hasAccessToChannel(new ArrayCollection([$channel])) === false)
               unset($channelEntities[array_search($channel, $channelEntities)]);
        }

        try {
            $mutes = [];
            foreach ($usernames as $username) {
                $user = $this->integration->getUserInfo(str_replace('@', '', $username))->getApiResponse()?->getUser();
                $mute = new Mute(
                    $muter,
                    new ArrayCollection($channelEntities),
                    str_replace('@', '', $username),
                    $user->getId(),
                    $command,
                    new \DateTime(sprintf('+%d days', $duration), new \DateTimeZone('UTC'))
                );
                $this->muteRepository->save($mute);

                $apiLog = $this->integration->mute($mute, true, $informChannel);
                if ($apiLog->isSuccess() === false)
                    return $this->returnText(sprintf("User %s couldn't be muted in %s", $username, implode(', ', $channels)));

                $mutes[] = $mute;
            }

            $apiLog = new ApiLog(
                true,
                json_encode((object) ['usernames' => $usernames, 'channels' => $channels, 'mute' => $mutes]),
                null,
                'webhook mute',
            );
            $this->apiLogRepository->save($apiLog);
        } catch (\Exception $e) {
            return $this->returnText("Couldn't preform mute", $e);
        }

        return JsonResponse::fromJsonString('{}');
    }

    private function handleWarning(string $command, User $user): JsonResponse
    {
        try {
            $parts = explode(' ', trim($command));
            $usernames = array_filter($parts, fn($part) => $part[0] === '@');
            $channels = array_filter($parts, fn($part) => $part[0] === '#');

            if (count($usernames) === 0 && count($channels) === 0)
                return JsonResponse::fromJsonString(json_encode([
                    'text' => "Username or channel is required"
                ]));
        } catch (\Exception $e) {
            return JsonResponse::fromJsonString(json_encode([
                'text' => "Couldn't parse input"
            ]));
        }

        try {
            $channelEntities = array_map(fn ($channel) => $this->channelRepository->findByName(str_replace('#', '', $channel)), $channels);
        } catch (\Exception $e) {
            return $this->returnText("Couldn't find the channel");
        }

        foreach ($channelEntities as $channel) {
            if ($user->hasAccessToChannel(new ArrayCollection([$channel])) === false)
                unset($channelEntities[array_search($channel, $channelEntities)]);
        }

        try {
            $reason = $command;
            foreach ($usernames as $username) {
                $reason = str_replace($username, '', $reason);
            }
            foreach ($channelEntities as $channel) {
                $reason = str_replace('#'. $channel->getName(), '', $reason);
            }

            foreach ($usernames as $username) {
                $warning = new Warning(
                    $user,
                    str_replace('@', '', $username),
                    null,
                    $reason
                );
                $this->warningRepository->save($warning);

                $this->integration->warn($warning);
            }

            foreach ($channelEntities as $channelEntity) {
                $warning = new Warning(
                    $user,
                    null,
                    $channelEntity->getIdentifier(),
                    $reason
                );
                $this->warningRepository->save($warning);

                $this->integration->warn($warning);
            }

            return $this->returnText('Warning has been sent');

        } catch (\Exception $exception) {
            return $this->returnText("Couldn't preform warning", $exception);
        }
    }

    private function handleTest(string $command, User $user, ?string $channelId): JsonResponse
    {
        if ($channelId) {
            $channel = $this->channelRepository->findByIdentifier($channelId);

            return $this->returnText(sprintf('Channel found! #%s', $channel->getName()));
        }

        try {
            $parts = explode(' ', trim($command));
            $usernames = array_filter($parts, fn($part) => $part[0] === '@');
            $channels = array_filter($parts, fn($part) => $part[0] === '#');

            if (count($usernames) === 0 || count($channels) === 0)
                return $this->returnText("Still here! :)");

        } catch (\Exception $e) {
            return $this->returnText("Couldn't parse input", $e);
        }

        try {
            array_map(fn ($channel) => $this->channelRepository->findByName(str_replace('#', '', $channel)), $channels);

        } catch (\Exception $e) {
            return $this->returnText("Couldn't find channels", $e);
        }

        return $this->returnText(sprintf('Usernames: %s, Channels: %s, Channels and muting user found!', implode(', ', $usernames), implode(', ', $channels)));
    }

    public function deactivate(User $user, string $input): Response
    {
        if ($user->isChatAdmin() === false)
            return $this->returnText('You are not allowed to deactivate users');

        try {
            $parts = explode(' ', trim($input));
            $usernames = array_filter($parts, fn($part) => $part[0] === '@');

            foreach ($usernames as $username) {
                $input = str_replace($username, '', $input);
            }

            if (count($usernames) === 0)
                return $this->returnText('Username is required');

            if (trim($input) === '')
                return $this->returnText('Reason for deactivation is required');

            foreach ($usernames as $username) {
                $this->integration->deactivateUser(str_replace('@', '', $username), $input, $user->getUsername());
            }

            return $this->returnText(sprintf('User %s has been deactivated', $usernames[0]));
        } catch (\Exception $e) {
            return $this->returnText("Couldn't parse input, please try again");
        }
    }

    public function showCommands(User $user): Response
    {
        $message = '
As an alternative from using the FapBot webportal ( https://fapbot.tech/ ),
You can use FapBot straight from chat by using some commands:

**You can run the following commands in any channel**:
* **!mute7**: Mutes a user for 7 days, contacts the user and updates the mute channels (`!mute7 @username [#channel] [optional reason]`).
* **!mute3**: Mutes a user for 3 days, contacts the user and updates the mute channels (`!mute3 @username [#channel] [optional reason]`).
    (When you don\'t provide a channel, the channel you\'re sending the command in will be used.)
* **!warning**: Sends a warning to a user or channel, you can do both or one of them (`!warning @username/#channel [optional reason]`).
* **!deactivate**: Deactivates a user and informs the deactivations channel (`!deactivate @username [optional reason]`).
* **!commands**: Shows this message.

Keep in mind that all users can see FapBots reply, so be careful with the !commands and !test commands
';

        return $this->returnText($message);
    }

    public function showMatterMostCommands(User $user): Response
    {
        $message = '
As an alternative from using the FapBot webportal ( https://fapbot.tech/ ),
You can use FapBot straight from chat by using some commands:

**You can run the following commands in any channel**:
* **/mute7**: (`/mute7 @username [optional reason]`).
    * Mutes a user for 7 days, contacts the user and updates the mute channels
    * (Always mutes the user in the current channel)
* **/mute3**: (`/mute3 @username [optional reason]`).
    * Mutes a user for 3 days, contacts the user and updates the mute channels
    * (Always mutes the user in the current channel)
* **/warning**: (`/warning @username [optional reason]`).
    * Sends a warning to a user or channel, you can do both or one of them
* **/deactivate**: (`/deactivate @username [reason]`).
    * Deactivates a user and informs the deactivations channel
* **/fapbot_commands**: Shows this message.
' ;

        return $this->returnText($message);
    }

    public function handleUserList(string $channelId): JsonResponse
    {
        $users = $this->integration->getChannelUsers($channelId);

        $mapUserNames = function (array $searchUsers): array {
            $usernames = array_map(fn ($user) => $user->getUsername(), $searchUsers);

            sort($usernames);

            return $usernames;
        };
        $section = function (string $name, array $users): string { return sprintf('
**%s** (%d)
%s', $name, count($users), implode(', ', array_map(fn ($user) => '@' . $user, $users))); };

        $onlineUsers = array_filter($users, fn ($user) => $user->getStatus() === 'online');
        $busyUsers = array_filter($users, fn ($user) => $user->getStatus() === 'busy');
        $awayUsers = array_filter($users, fn ($user) => $user->getStatus() === 'away');

        $usersList = [
            sprintf('**Total Online: %d**', count($users)),
            '*Because of how statuses are handled in this chat this list might be incomplete or show some users who\'re actually offline*',
            $section('Online', $mapUserNames($onlineUsers)),
        ];

        if (count($busyUsers) > 0) {
            $usersList[] = $section('Busy', $mapUserNames($busyUsers));
        }

        if (count($awayUsers) > 0) {
            $usersList[] = $section('Away', $mapUserNames($awayUsers));
        }

        return $this->returnText(implode("\n", $usersList));
    }

    private function returnText(string $text, \Exception $exception = null): JsonResponse
    {
        $message = [
            'text' => $text
        ];

        if ($exception !== null)
            $message['attachments'] = [
                [
                    'text' => $exception->getMessage()
                ]
            ];

        return JsonResponse::fromJsonString(json_encode($message));
    }
}