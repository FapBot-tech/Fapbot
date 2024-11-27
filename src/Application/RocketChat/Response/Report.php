<?php
declare(strict_types=1);

namespace App\Application\RocketChat\Response;


final class Report
{
    public ?string $reportId;
    public ?string $roomId;
    public ?string $roomName;
    public ?string $message;
    public ?string $msgId;
    public ?string $username;
    public ?string $userId;
    public ?\DateTimeImmutable $timeStamp;
    public ?string $description;
    public ?string $reportedBy;
    /** @var Message[]|null $messages */
    public ?array $messages = [];

    public function __construct(array $report)
    {
        if (array_key_exists('rooms', $report)) {
            if (array_key_exists('name', $report['rooms'][0])) {
                $this->roomName = $report['rooms'][0]['name'];
            } else {
                $this->roomName = 'Private messages';
            }
            $this->roomId = $report['rooms'][0]['_id'];
        } elseif (array_key_exists('room', $report)) {
            if (array_key_exists('name', $report['room'])) {
                $this->roomName = $report['room']['name'];
            } else {
                $this->roomName = 'Private messages';
            }
            $this->roomId = $report['room']['_id'];
        }

        if (array_key_exists('description', $report))
            $this->description = $report['description'];

        if (array_key_exists('reportedBy', $report))
            $this->reportedBy = $report['reportedBy']['username'];

        if (array_key_exists('_id', $report))
            $this->reportId = $report['_id'];

        if (array_key_exists('message', $report)) {
            $this->message = $report['message'];

            if ($report['message'] === '')
                $this->message = 'Uploaded image';
        }

        if (array_key_exists('msgId', $report))
            $this->msgId = $report['msgId'];

        if (array_key_exists('username', $report))
            $this->username = $report['username'];

        if (array_key_exists('userId', $report))
            $this->userId = $report['userId'];

        if (array_key_exists('ts', $report))
            $this->timeStamp = new \DateTimeImmutable($report['ts'], new \DateTimeZone('UTC'));
    }
}