<?php
declare(strict_types=1);

namespace App\Application\RocketChat;

use App\Application\Chat\IntegrationInterface;
use App\Application\RocketChat\Response\Message;
use App\Application\RocketChat\Response\Report;
use App\Application\RocketChat\Response\Response;
use App\Entity\Announcement;
use App\Entity\ApiLog;
use App\Entity\Channel;
use App\Entity\Mute;
use App\Entity\User;
use App\Entity\Warning;
use DateTimeImmutable;


final class RocketChatIntegration implements IntegrationInterface
{
    private Integration $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    public function mute(Mute $mute, bool $shouldInformTeam = true, bool $shouldInformChannel = false): ApiLog
    {
        $this->integration->contactUserForMute($mute);

        if ($shouldInformTeam)
            $this->integration->updateMuteChannels($mute);

        if ($shouldInformChannel) {
            foreach ($mute->getChannels() as $channel)
                $this->integration->contactChannelForMute($mute, $channel);
        }

        return $this->integration->mute($mute);
    }

    public function unmute(Mute $mute): ApiLog
    {
        return $this->integration->unmute($mute);
    }

    public function warn(Warning $warning, bool $shouldContactUser = true, bool $shouldInformTeam = true): ApiLog
    {
        if ($shouldContactUser)
            $result = $this->integration->contactUserForWarning($warning);

        if ($shouldInformTeam)
            $result = $this->integration->updateWarningChannel(
                $warning->getUserName() ?? $warning->getChannelId(),
                $warning->getReason(),
                $warning->getUser()
            );

        return $result ?? ApiLog::createEmptySuccess();
    }

    public function announcement(Announcement $announcement): ApiLog
    {
        $this->integration->sendAnnouncement($announcement);

        return ApiLog::createEmptySuccess();
    }

    public function getUserList(): ApiLog
    {
        $users = $this->integration->getUserList();
        return ApiLog::createFromResponse(Response::createFromUsers($users));
    }

    public function deactivateUser(string $username, string $reason, string $deactivatingUser = null): ApiLog
    {
        return $this->integration->deactivateUser($username);
    }

    public function activateUser(string $username, ?string $deactivatingUser = null): ApiLog
    {
        return $this->integration->activateUser($username);
    }

    public function getUserInfo(string $username): ApiLog
    {
        $user = $this->integration->getUserInfo($username);

        if ($user === null)
            return ApiLog::createEmptyError();

        return ApiLog::createFromResponse(Response::createFromUser($user));
    }

    public function autoCompleteUsername(string $partial): ApiLog
    {
        $response = $this->integration->suggestUsernames($partial);

        return ApiLog::createFromResponse($response);

    }

    public function getChannelMessages(Channel $channel, int $perPage = 200): ApiLog
    {
        $messages = $this->integration->getChannelMessages($channel);

        return ApiLog::createFromResponse(Response::createFromMessages($messages));
    }

    public function deleteMessage(string $messageId, string $channelId = null): ApiLog
    {
        $response = $this->integration->deleteMessage($messageId, $channelId);

        return ApiLog::createFromResponse($response);
    }

    public function problematicUser(string $username, int $weeklyMuteCount, int $monthlyMuteCount, int $weeklyWarningCount, int $monthlyWarningCount): ApiLog
    {
        return $this->integration->updateProblematicUsersChannel(
            sprintf(
                '**New mute or warning created for problematic user: %s**
User muted %d times in the last week, %d times in the last month.
User warned %d times in the last week, %d times in the last month.

https://fapbot.tech/chat_user/%s',
                $username,
                $weeklyMuteCount,
                $monthlyMuteCount,
                $weeklyWarningCount,
                $monthlyWarningCount,
                $username
            )
        );
    }

    public function sendPasswordResetLink(User $user, string $resetLink): ApiLog
    {
        return $this->integration->sendPasswordResetLink($user, $resetLink);
    }

    public function getReportedMessages(?DateTimeImmutable $start = null, ?DateTimeImmutable $end = null): Response
    {
        return $this->integration->getReportedMessages($start, $end);
    }

    public function getUserReports(string $userId): Response
    {
        return $this->integration->getUserReports($userId);
    }

    public function getMessageReports(string $messageId): Response
    {
        return $this->integration->getMessageReports($messageId);
    }

    public function dismissUserReports(string $userId): Response
    {
        return $this->integration->dismissUserReports($userId);
    }

    public function deleteReportedMessages(string $userId): Response
    {
        return $this->integration->deleteReportedMessages($userId);
    }

    public function shareReportInChat(Report $report, ?Message $message): ApiLog
    {
        return $this->integration->shareReportInChat($report, $message);
    }

    public function getGroupMessages(string $id, \DateTimeImmutable $start): array
    {
        return $this->integration->getGroupMessages($id, $start);
    }

    public function reactToMessage(string $id, string $emoji, bool $add = true): ApiLog
    {
        return $this->integration->reactToMessage($id, $emoji, $add);
    }

    public function checkForBlocked(string $username): ApiLog
    {
        return $this->integration->checkForBlocked($username);
    }

    public function getImageLink(string $imageId): ApiLog
    {
        return ApiLog::createEmptyError();
    }

    public function sendReport(\App\Entity\Report $report): ApiLog
    {
        return ApiLog::createEmptyError();
    }
}