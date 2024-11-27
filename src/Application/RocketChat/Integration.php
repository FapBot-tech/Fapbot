<?php
declare(strict_types=1);

namespace App\Application\RocketChat;

use App\Application\RocketChat\Response\Message;
use App\Application\RocketChat\Response\Report;
use App\Application\RocketChat\Response\Response;
use App\Application\RocketChat\Response\Users;
use App\Entity\Announcement;
use App\Entity\ApiLog;
use App\Entity\BlockedUser;
use App\Entity\Channel;
use App\Entity\Mute;
use App\Entity\Repository\ApiLogRepository;
use App\Entity\Repository\BlockedUserRepository;
use App\Entity\Repository\ChannelRepository;
use App\Entity\User;
use App\Entity\Warning;
use DateTimeImmutable;
use Twig\Environment;
use App\Application\RocketChat\Response\User as RocketChatUser;

final class Integration {
    private ApiLogRepository $apiLogRepository;
    private BlockedUserRepository $blockedUserRepository;
    private Environment $environment;
    private Connector $connector;
    private ChannelRepository $channelRepository;
    
    public function __construct(
        ApiLogRepository $apiLogRepository,
        BlockedUserRepository $blockedUserRepository,
        Environment $environment,
        Connector $connector,
        ChannelRepository $channelRepository,
    ) {
        $this->apiLogRepository = $apiLogRepository;
        $this->blockedUserRepository = $blockedUserRepository;
        $this->environment = $environment;
        $this->connector = $connector;
        $this->channelRepository = $channelRepository;
    }

    # section: Mutes
    public function mute(Mute $mute): ApiLog
    {
        $muteSuccess = true;

        foreach($mute->getChannels() as $channel){
            $response = $this->connector->runCommand(sprintf('command=mute&roomId=%s&params=%s', $channel->getIdentifier(), $mute->getUserName()));

            if($response->success === false)
                $muteSuccess = false;
        }

        $apiLog = new ApiLog(
            $muteSuccess,
            $response->response,
            json_encode((object) [
                'message' => sprintf('Muting %s', $mute->getUserName()),
            ])
        );

        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    public function unmute(Mute $mute): ApiLog
    {
        $muteSuccess = true;

        foreach($mute->getChannels() as $channel){
            $response = $this->connector->runCommand(sprintf('command=unmute&roomId=%s&params=%s', $channel->getIdentifier(), $mute->getUserName()));

            if($response->success === false)
                $muteSuccess = false;
        }

        $apiLog = new ApiLog(
            $muteSuccess,
            $response->response,
            json_encode((object) [
                'message' => sprintf('Unmuting %s', $mute->getUserName()),
            ])
        );

        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    public function contactUserForMute(Mute $mute): ApiLog
    {
        $messageText = sprintf(
            "**Automatic message**: You've been muted in the following channel(s): %s, for %d day(s), this mute is non negotiable but if you have any questions contact @%s",
            $mute->getChannelsString(),
            $mute->getDurationInDays(),
            $mute->getUser()->getUsername()
        );
        $username = sprintf('@%s', $mute->getUserName());

        $message = (new ChatMessage($username, $messageText))
            ->addAttachement($mute->getReason(), true)
            ->addColor('#ff0000');

        $response = $this->connector->postMessage($message->getMessage());
        $apiLog = ApiLog::createFromRocketChatResponse($response, $message->getMessage());
        
        $this->detectAndRegisterUserBlock($apiLog, $mute->getUserName());
        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    public function contactChannelForMute(Mute $mute, Channel $channel): ApiLog
    {
        $messageText = sprintf(
            "**Automatic message**: @%s has been muted",
            $mute->getUserName()
        );

        $message = (new ChatMessage($channel->getIdentifier(), $messageText))
            ->addAttachement($mute->getReason(), false)
            ->addColor('#FF0000');

        $response = $this->connector->postMessage($message->getMessage());
        $apiLog = ApiLog::createFromRocketChatResponse($response, $message->getMessage());

        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    public function updateMuteChannels(Mute $mute): ApiLog
    {
        $threeDayMuteID = "2AYSoHtdDNtuS7pRt";
        $sevenDayMuteID = "BTXbDwDyMndmENbGx";
        $channelId = $mute->getDurationInDays() <= 3 ? $threeDayMuteID : $sevenDayMuteID;

        $messageText = sprintf(
            "@%s has been muted in %s for %d days by @%s",
            $mute->getUserName(),
            $mute->getChannelsString(),
            $mute->getDurationInDays(),
            $mute->getUser()->getUsername()
        );

        $message = (new ChatMessage($channelId, $messageText))
            ->addAttachement($mute->getReason(), false);

        $response = $this->connector->postMessage($message->getMessage());
        $apiLog = ApiLog::createFromRocketChatResponse($response, $message->getMessage());

        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    # section: Warnings
    public function contactUserForWarning(Warning $warning): ApiLog
    {
        $target = '';
        if ($warning->getUserName() !== null)
            $target = sprintf('@%s', $warning->getUserName());

        if ($warning->getChannelId() !== null) {
            $channel = $this->channelRepository->findByIdentifier($warning->getChannelId());
            $target = '#' . $channel->getName();
        }

        $message = (new ChatMessage($target, "**Automatic message** Warning provided by moderator or admin"))
            ->addAttachement($warning->getReason(), true)
            ->addColor('#FFA500');

        $response = $this->connector->postMessage($message->getMessage());
        $apiLog = ApiLog::createFromRocketChatResponse($response, $message->getMessage());

        $this->detectAndRegisterUserBlock($apiLog, $target);
        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    public function updateWarningChannel(string $target, string $reason, User $user): ApiLog
    {
        $targets = explode(' ', $target);

        foreach ($targets as $target) {
            $channel = $this->channelRepository->findByIdentifier($target);

            if ($channel !== null) {
                $target = str_replace($targets, $target, '#' . $channel->getName());
            } else {
                $target = str_replace($targets, $target, '@' . $target);

            }
        }

        if (count($targets) > 1)
            $messageText = sprintf('%s have been warned by @%s', $target, $user->getUsername());
        else
            $messageText = sprintf('%s has been warned by @%s', $target, $user->getUsername());

        $warningId = 'hwLvzvMFSJXnbduCQ';

        $message = (new ChatMessage($warningId, $messageText))
            ->addAttachement($reason, false);

        $response = $this->connector->postMessage($message->getMessage());
        $apiLog = ApiLog::createFromRocketChatResponse($response, $message->getMessage());

        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    # section: Announcement
    public function sendAnnouncement(Announcement $announcement): void
    {
        $message = $announcement->getHeader() !== null ? sprintf('**%s**
%s', $announcement->getHeader(), $announcement->getText()) : $announcement->getText();

        foreach ($announcement->getChannels() as $channel) {
            $message = (new ChatMessage($channel->getIdentifier(), $message));

            if($announcement->getLinks() !== null)
                $message->addAttachement($announcement->getLinks(), false);

            $this->connector->postMessage($message->getMessage());
        }
    }

    # section: User
    public function sendPasswordResetLink(User $user, string $resetLink): ApiLog
    {
        $messageText = sprintf(
            "**Automatic message**: A new password was requested \r\n %s",
            $resetLink
        );

        $message = (new ChatMessage(sprintf('@%s', $user->getUsername()), $messageText))
            ->addColor('#FFA500');

        $response = $this->connector->postMessage($message->getMessage());
        $apiLog = ApiLog::createFromRocketChatResponse($response, $message->getMessage());

        $this->detectAndRegisterUserBlock($apiLog, $user->getUserName());
        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    public function checkForBlocked(string $username): ApiLog
    {
        $message = (new ChatMessage(sprintf('@%s', $username), '**Automatic message**: Thank you for unblocking FapBot'));

        $response = $this->connector->postMessage($message->getMessage());
        $apiLog = ApiLog::createFromRocketChatResponse($response, $message->getMessage());

        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    public function getUserList(): Users
    {
        $users = new Users([]);
        $statuses = [
            'online',
            'away',
            'busy',
            'invisible'
        ];

        foreach ($statuses as $status) {
            $page = 1;
            $statusUsers = [];

            do {
                $response = $this->connector->getUsers($status, $page);
                $page++;

                if ($response->success === false)
                    continue;

                $statusUsers = array_merge($statusUsers, $response->users?->users ?? []);
            } while (count($statusUsers) < $response->total);

            $users->addUsers($statusUsers);
        }

        return $users;
    }

    public function deactivateUser(string $username): ApiLog
    {
        $user = $this->connector->getUserInfo($username);
        $response = $this->connector->updateUser($user->user->_id, false);

        $apiLog = ApiLog::createFromRocketChatResponse($response, sprintf('Deactivating user %s', $username));
        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    public function activateUser(string $username): ApiLog
    {
        $user = $this->connector->getUserInfo($username);
        $response = $this->connector->updateUser($user->user->_id, true);

        $apiLog = ApiLog::createFromRocketChatResponse($response, sprintf('Activating user %s', $username));
        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    public function getUserInfo(string $username): ?RocketChatUser
    {
        $response = $this->connector->getUserInfo($username);

        if ($response->user === null) {
            $suggestions = $this->connector->autoCompleteUsername($username)->suggestions;
            if (isset($suggestions) && count($suggestions->suggestions) === 1) {
                $user = $this->getUserInfo($suggestions->suggestions[0]?->username);
                $user->usernameCorrected = true;

                return $user;
            }
        }

        $apiLog = ApiLog::createFromRocketChatResponse($response);

        $this->apiLogRepository->save($apiLog);

        if($response->success === false)
            return null;

        return $response->user;
    }

    public function fixProfile(RocketChatUser $user): ApiLog
    {
        $response = $this->connector->fixUserProfile($user->_id, $user->username, $user->gender);

        $apiLog = ApiLog::createFromRocketChatResponse($response, sprintf('Fixing profile for user %s', $user->username));
        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    // section: Messages
    public function getChannelMessages(Channel $channel): array
    {
        $response = $this->connector->getChannelMessages($channel->getIdentifier(), 100);

        return $response?->messages?->messages ?? [];
    }

    public function getGroupMessages(string $id, \DateTimeImmutable $start): array
    {
        $response = $this->connector->getGroupMessages($id, $start);

        return $response->messages->messages;
    }

    public function reactToMessage(string $id, string $emoji, bool $add = true): ApiLog
    {
        $response = $this->connector->reactToMessage($id, $emoji, $add);

        $apiLog = ApiLog::createFromRocketChatResponse($response, sprintf('Reacting to message %s with %s', $id, $emoji));
        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    // section: Problem users
    public function updateProblematicUsersChannel(string $message): ApiLog
    {
        $message = (new ChatMessage('mHgAx723saopkKv2z', $message));

        $response = $this->connector->postMessage($message->getMessage());
        $apiLog = ApiLog::createFromRocketChatResponse($response, $message->getMessage());

        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    public function getReportedMessages(?DateTimeImmutable $start = null, ?DateTimeImmutable $end = null): Response
    {
        $response = $this->connector->getReportsByUser($start, $end);

        $apiLog = ApiLog::createFromRocketChatResponse($response);

        $this->apiLogRepository->save($apiLog);

        return $response;
    }

    public function getUserReports(string $userId): Response
    {
        $response = $this->connector->getReportsForUser($userId);

        $apiLog = ApiLog::createFromRocketChatResponse($response);

        $this->apiLogRepository->save($apiLog);

        return $response;
    }

    public function getMessageReports(string $messageId): Response
    {
        $response = $this->connector->getReportsForMessage($messageId);

        $apiLog = ApiLog::createFromRocketChatResponse($response);
        $this->apiLogRepository->save($apiLog);

        return $response;
    }

    public function dismissUserReports(string $userId): Response
    {
        $response = $this->connector->dismissReports($userId);
        $apiLog = ApiLog::createFromRocketChatResponse($response);
        $this->apiLogRepository->save($apiLog);

        return $response;
    }

    public function deleteReportedMessages(string $userId): Response
    {
        $response = $this->connector->deletedUserReportedMessages($userId);
        $apiLog = ApiLog::createFromRocketChatResponse($response);
        $this->apiLogRepository->save($apiLog);

        return $response;
    }

    public function shareReportInChat(Report $report, ?Message $message): ApiLog
    {
        $messageText = '**Automatic message**: A message from: ' . $report->username . ' has been reported';
        $messageText .= '
        Link to message: ' . sprintf('https://chat.imagefap.com/channel/%s?msg=%s', $report->roomName, $report->msgId);

        $chatMessage = (new ChatMessage('65a2569f96d00810f45e881e', $messageText));

        if ($message?->isImage())
            $chatMessage->addAttachement($message->getImage(), false, true);

        $chatMessage->addAttachement($report->message, false, str_starts_with($report->message, 'https://'));

        $response = $this->connector->postMessage($chatMessage->getMessage());
        $apiLog = ApiLog::createFromRocketChatResponse($response, $chatMessage->getMessage());

        $this->apiLogRepository->save($apiLog);

        return $apiLog;
    }

    public function deleteMessage(string $messageId, string $channelId): Response
    {
        $response = $this->connector->deleteMessage($messageId, $channelId);
        $apiLog = ApiLog::createFromRocketChatResponse($response);
        $this->apiLogRepository->save($apiLog);

        return $response;
    }

    public function suggestUsernames(string $username): Response
    {
        $response = $this->connector->autoCompleteUsername($username);

        $apiLog = ApiLog::createFromRocketChatResponse($response);
        $this->apiLogRepository->save($apiLog);

        return $response;
    }

    private function isLastMessageFromBot(string $channel): bool
    {
        $response = $this->connector->getChannelMessages($channel, 1);

        if ($response->messages === null || $response->messages->messages === [])
            return false;

        $apiLog = ApiLog::createFromRocketChatResponse($response);
        $this->apiLogRepository->save($apiLog);

        $lastMessage = $response->messages?->getFirstMessage();
        $lastUser = $lastMessage->u;

        if ($lastUser->username !== 'fapbot')
            return false;

        if (str_contains($lastMessage->msg, 'Automatic message'))
            return false;

        return true;
    }

    private function detectAndRegisterUserBlock(ApiLog $apiLog, string $target): void
    {
        if($apiLog->getExtractedError() !== 'This user has blocked FapBots direct messages. The direct message to the user with the reason was thus not received. (message 2 in the mute guide)')
            return;

        $block = new BlockedUser($target);
        $this->blockedUserRepository->save($block);
    }
}