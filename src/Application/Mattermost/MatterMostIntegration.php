<?php
declare(strict_types=1);

namespace App\Application\Mattermost;

use App\Application\Chat\IntegrationInterface;
use App\Application\Mattermost\Response\Message;
use App\Entity\Announcement;
use App\Entity\ApiLog;
use App\Entity\Channel;
use App\Entity\Mute;
use App\Entity\Report;
use App\Entity\Repository\ApiLogRepository;
use App\Entity\Repository\BlockedUserRepository;
use App\Entity\Repository\ChannelRepository;
use App\Entity\User;
use App\Entity\Warning;
use App\Form\Validation\ProblematicUserValidator;
use Twig\Environment;


final class MatterMostIntegration implements IntegrationInterface
{
    public const MUTE_CHANNEL_3 = 'kntobhzzepypjjxuqw7c1uh89w';
    public const MUTE_CHANNEL_7 = 'bhgky9y8g3di7mofbwytnfc5ka';
    public const WARNING_CHANNEL = '1i98xxrsb7bq5cc3kssczyd6sy';
    public const PROBLEM_USERS_CHANNEL = "dhcaa4593j8idy6nbi6n6wa83y";
    public const DEACTIVATIONS_CHANNEL = '395znbazmbdou897tc4cshgegy';
    public const REPORT_CHANNEL = '5c7yj5zw83bu3donceu1bb44iy';
    public const BOT_USER_ID = 'cai3rqeh57895xha3i6ainmawa';

    private ApiLogRepository $apiLogRepository;
    private BlockedUserRepository $blockedUserRepository;
    private Environment $environment;
    private Connector $connector;
    private ChannelRepository $channelRepository;

    public function __construct(
        ApiLogRepository      $apiLogRepository,
        BlockedUserRepository $blockedUserRepository,
        Environment           $environment,
        Connector             $connector,
        ChannelRepository     $channelRepository
    )
    {
        $this->apiLogRepository = $apiLogRepository;
        $this->blockedUserRepository = $blockedUserRepository;
        $this->environment = $environment;
        $this->connector = $connector;
        $this->channelRepository = $channelRepository;
    }

    public function mute(Mute $mute, bool $shouldInformTeam = true, bool $shouldInformChannel = false): ApiLog
    {
        $chatMessage = new ChatMessage(
            $mute->getUserId(),
            sprintf(
                "**Automatic message**: You've been muted in the following channel(s): %s, for %d day(s), this mute is non negotiable but if you have any questions contact @%s",
                $mute->getChannelsString(),
                $mute->getDurationInDays(),
                $mute->getUser()->getUsername(),
            )
        );
        $chatMessage->addAttachment($mute->getReason(), true);

        $response = $this->connector->sendMessageToUser($chatMessage);
        $this->apiLogRepository->save(ApiLog::createFromResponse($response));

        if ($shouldInformTeam) {
            $channelId = $mute->getDurationInDays() <= 3 ? self::MUTE_CHANNEL_3 : self::MUTE_CHANNEL_7;

            $chatMessage = new ChatMessage(
                $channelId,
                sprintf(
                    "**Automatic message**: @%s has been muted in the following channel(s): %s, for %d day(s), by @%s",
                    $mute->getUsername(),
                    $mute->getChannelsString(),
                    $mute->getDurationInDays(),
                    $mute->getUser()->getUsername(),
                )
            );
            $chatMessage->addColor('#FF0000');
            $chatMessage->addAttachment($mute->getReason(), true);
            $response = $this->connector->sendMessageToChannel($chatMessage);
        }

        $success = true;

        if ($shouldInformChannel) {
            foreach ($mute->getChannels() as $channel) {
                $chatMessage = new ChatMessage(
                    $channel->getIdentifier(),
                    sprintf(
                        "**Automatic message**: @%s has been muted in this channel for %d day(s)",
                        $mute->getUsername(),
                        $mute->getDurationInDays(),
                    )
                );
                $chatMessage->addAttachment($mute->getReason(), true);
                $chatMessage->addColor('#FF0000');

                $response = $this->connector->sendMessageToChannel($chatMessage);
                if ($response->success === false) {
                    $success = false;
                }
            }
        }

        usleep(5000);
        $this->connector->sendMessageToChannel(new ChatMessage(self::MUTE_CHANNEL_3, 'Reset mute list'));

        $apiLog = $this->apiLogRepository->save(ApiLog::createFromResponse($response));
        $apiLog->setSuccess($success);

        return $apiLog;
    }

    public function unmute(Mute $mute): ApiLog
    {
        usleep(5000);
        $response = $this->connector->sendMessageToChannel(new ChatMessage(self::MUTE_CHANNEL_3, 'Reset mute list'));

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }

    public function warn(Warning $warning, bool $shouldContactUser = true, bool $shouldInformTeam = true): ApiLog
    {
        if ($shouldContactUser) {
            if ($warning->getUserName() !== null) {
                $user = $this->connector->getUserInfo($warning->getUserName())->getUser();
                $message = new ChatMessage(
                    $user->getId(),
                    "**Automatic message**: Warning provided by moderator or admin!",
                );
                $message->addColor('#FFA500');
                $message->addAttachment($warning->getReason(), true);
                $response = $this->connector->sendMessageToUser($message);

            } else {
                $message = new ChatMessage(
                    $warning->getChannelId(),
                    "**Automatic message**: Warning provided by moderator or admin!",
                );
                $message->addAttachment($warning->getReason(), true);
                $message->addColor('#FFA500');

                $response = $this->connector->sendMessageToChannel($message);
            }

            $this->apiLogRepository->save(ApiLog::createFromResponse($response));
        }

        if ($shouldInformTeam) {
            if ($warning->getUserName())
                $target = '@' . $warning->getUserName();
            else
                $target = '~' . $this->channelRepository->findByIdentifier($warning->getChannelId())->getName();


            $message = new ChatMessage(
                self::WARNING_CHANNEL,
                sprintf(
                    "**Automatic message**: %s has been warned, by @%s",
                    $target,
                    $warning->getUser()->getUsername(),
                )
            );
            $message->addAttachment($warning->getReason(), true);

            $response = $this->connector->sendMessageToChannel($message);
            $this->apiLogRepository->save(ApiLog::createFromResponse($response));
        }

        if (
            ($shouldInformTeam === false && $shouldContactUser === false) ||
            isset($response) === false
        ) {
            return ApiLog::createEmptySuccess();
        }

        return ApiLog::createFromResponse($response);
    }

    public function announcement(Announcement $announcement): ApiLog
    {

        $message = $announcement->getHeader() !== null ? sprintf('**%s**
%s', $announcement->getHeader(), $announcement->getText()) : $announcement->getText();

        foreach ($announcement->getChannels() as $channel) {
            $channelMessages = $this->connector->getChannelMessages($channel->getIdentifier(), 200)->getMessages()->getMessages();
            $botMessages = array_filter($channelMessages, function (Message $message) {
                return $message->getUserId() === self::BOT_USER_ID;
            });

            $announcementMessages = array_filter($botMessages, function (Message $message) use ($announcement) {
                $firstLine = str_replace('\\r', '', str_replace('\\n', '', explode("\r\n", $announcement->getText())[0]));

                return str_contains($message->getMessage(), $firstLine);
            });

            foreach ($announcementMessages as $announcementMessage) {
                $this->connector->deleteMessage($announcementMessage->getId());
            }

            $chatmessage = (new ChatMessage($channel->getIdentifier(), $message));

            if ($announcement->getLinks() !== null)
                $chatmessage->addAttachment($announcement->getLinks(), false);

            $response = $this->connector->sendMessageToChannel($chatmessage);
            $this->apiLogRepository->save(ApiLog::createFromResponse($response));
        }

        return ApiLog::createFromResponse($response);
    }

    public function getUserList(): ApiLog
    {
        $response = $this->connector->getUsers(1);

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }

    public function getChannelUsers(string $channelId): array
    {
        $loops = 1;
        $page = 1;
        $onlineUsers = [];
        $isLastUserOnline = true;

        do {
            $isPreviousLastUserOnline = $isLastUserOnline;

            $users = $this->connector->getChannelUsers($channelId, $page)->getUsers()->users;

            foreach ($users as $user) {
                $status = $this->connector->getUserStatus($user->getId());
                $user->setStatus($status);

                if ($status !== 'offline') {
                    $onlineUsers[] = $user;
                }
            }

            $lastUser = $users[count($users) - 1];
            $isLastUserOnline = $lastUser->getStatus() !== 'offline';
            $loops++;
            $page++;
        } while (count($users) === 200 && $isLastUserOnline && $isPreviousLastUserOnline && $loops <= 10);

        return $onlineUsers;
    }

    public function deactivateUser(string $username, ?string $reason, ?string $deactivatingUser = null): ApiLog
    {
        $user = $this->connector->getUserInfo($username, true)->getUser();

        $message = new ChatMessage(
            self::DEACTIVATIONS_CHANNEL,
            sprintf(
                "**Automatic message**: @%s has been deactivated by @%s",
                $username,
                $deactivatingUser
            )
        );
        if ($reason !== null && trim($reason) !== '')
            $message->addAttachment($reason, true);

        $this->connector->sendMessageToChannel($message);

        $response = $this->connector->deactivateUser($user->getId(), $username);

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }

    public function activateUser(string $username, ?string $deactivatingUser = null): ApiLog
    {
        $user = $this->connector->getUserInfo($username, true)->getUser();

        $message = new ChatMessage(
            self::DEACTIVATIONS_CHANNEL,
            sprintf(
                "**Automatic message**: @%s has been **re-activated** by @%s",
                $username,
                $deactivatingUser
            )
        );
        $this->connector->sendMessageToChannel($message);

        $response = $this->connector->activateUser($user->getId(), $username);

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }

    public function getUserInfo(string $username): ApiLog
    {
        $response = $this->connector->getUserInfo($username);
        if ($response->user === null)
            return ApiLog::createEmptyError();

        $status = $this->connector->getUserStatus($response->getUser()->getId());
        $response->getUser()->setStatus($status);

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }

    public function autoCompleteUsername(string $partial): ApiLog
    {
        $response = $this->connector->autoCompleteUsername($partial);

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }

    public function getChannelMessages(Channel $channel, int $perPage = 200): ApiLog
    {
        $response = $this->connector->getChannelMessages($channel->getIdentifier(), $perPage);

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }

    public function deleteMessage(string $messageId, string $channelId = null): ApiLog
    {
        $response = $this->connector->deleteMessage($messageId);

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }

    public function problematicUser(string $username, int $weeklyMuteCount, int $monthlyMuteCount, int $weeklyWarningCount, int $monthlyWarningCount): ApiLog
    {
        $message = new ChatMessage(
            self::PROBLEM_USERS_CHANNEL,
            sprintf(
                '**New mute or warning created for problematic user: %s**
User muted %d times in the last week, %d times in the last month.
User warned %d times in the last week, %d times in the last month.
%s

https://fapbot.tech/chat_user/%s',
                $username,
                $weeklyMuteCount,
                $monthlyMuteCount,
                $weeklyWarningCount,
                $monthlyWarningCount,
                in_array($username, ProblematicUserValidator::PREVIOUS_DEACTIVATIONS) ? '**This user was previously deactivated in RocketChat, depending on the violation, deactivate them again.**' : '',
                $username
            )
        );
        $response = $this->connector->sendMessageToChannel($message);

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }

    public function sendPasswordResetLink(User $user, string $resetLink): ApiLog
    {
        $messageText = sprintf(
            "**Automatic message**: A new password was requested \r\n %s",
            $resetLink
        );
        $chat_user = $this->connector->getUserInfo(strtolower($user->getUsername()))->getUser();

        $message = new ChatMessage(
            $chat_user->getId(),
            $messageText
        );

        $response = $this->connector->sendMessageToUser($message);

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }

    public function getImageLink(string $imageId): ApiLog
    {
        $response = $this->connector->getPublicLink($imageId);

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }

    public function sendReport(Report $report): ApiLog
    {
        $chatMessage = new ChatMessage(
            self::REPORT_CHANNEL,
            sprintf(
                "**Automatic message**: @%s has filled in a report against @%s %s",
                $report->getReporterName(),
                $report->getReportedName(),
                $report->getReportedMessageLink() ? '

Message link:
' . $report->getReportedMessageLink() : ''
            )
        );
        $chatMessage->addAttachment($report->getReason(), true);

        $response = $this->connector->sendMessageToChannel($chatMessage);

        return $this->apiLogRepository->save(ApiLog::createFromResponse($response));
    }
}