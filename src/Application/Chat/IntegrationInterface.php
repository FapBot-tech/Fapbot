<?php
declare(strict_types=1);

namespace App\Application\Chat;

use App\Entity\Announcement;
use App\Entity\ApiLog;
use App\Entity\Channel;
use App\Entity\Mute;
use App\Entity\Report;
use App\Entity\User;
use App\Entity\Warning;

interface IntegrationInterface
{
    public function mute(Mute $mute, bool $shouldInformTeam = true, bool $shouldInformChannel = false): ApiLog;
    public function unmute(Mute $mute): ApiLog;
    public function warn(Warning $warning, bool $shouldContactUser = true, bool $shouldInformTeam = true): ApiLog;
    public function announcement(Announcement $announcement): ApiLog;
    public function getUserList(): ApiLog;
    public function deactivateUser(string $username, string $reason, string $deactivatingUser = null): ApiLog;
    public function activateUser(string $username, ?string $deactivatingUser = null): ApiLog;
    public function getUserInfo(string $username): ApiLog;
    public function autoCompleteUsername(string $partial): ApiLog;
    public function getChannelMessages(Channel $channel, int $perPage = 200): ApiLog;
    public function deleteMessage(string $messageId, string $channelId = null): ApiLog;
    public function problematicUser(string $username, int $weeklyMuteCount, int $monthlyMuteCount, int $weeklyWarningCount, int $monthlyWarningCount): ApiLog;
    public function sendPasswordResetLink(User $user, string $resetLink): ApiLog;
    public function getImageLink(string $imageId): ApiLog;
    public function sendReport(Report $report): ApiLog;
}