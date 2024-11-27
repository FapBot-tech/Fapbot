<?php
declare(strict_types=1);

namespace App\Application\Mattermost\Response;

use App\Application\Chat\Response\MessageInterface;
use DateTimeImmutable;


final class Message implements MessageInterface
{
    public const TYPE = 'mattermost';

    public string $id;
    public string $channelId;
    public string $userId;
    public string $rootId;
    public string $originalId;
    public DateTimeImmutable $createdAt;
    public ?DateTimeImmutable $updatedAt;
    public ?DateTimeImmutable $editAt;
    public ?DateTimeImmutable $deletedAt;
    public bool $isPinned;
    public string $msg;
    public string $type;
    public ?string $timeAgo;
    public ?string $file;
    public array $files = [];
    public ?string $publicUrl = null;

    public function __construct(array $message)
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->id = $message['id'];
        $this->channelId = $message['channel_id'];
        $this->userId = $message['user_id'];
        $this->rootId = $message['root_id'];
        $this->originalId = $message['original_id'];
        $this->createdAt = DateTimeImmutable::createFromMutable($date->setTimestamp((int)floor($message['create_at'] / 1000)));
        $this->updatedAt = isset($message['update_at']) ? DateTimeImmutable::createFromMutable($date->setTimestamp((int)floor($message['update_at'] / 1000))) : null;
        $this->editAt = isset($message['edit_at']) ? DateTimeImmutable::createFromMutable($date->setTimestamp((int)floor($message['edit_at'] / 1000))) : null;
        $this->deletedAt = isset($message['delete_at']) ? DateTimeImmutable::createFromMutable($date->setTimestamp((int)floor($message['delete_at'] / 100))) : null;
        $this->isPinned = $message['is_pinned'];
        $this->msg = $message['message'];
        $this->type = $message['type'];

        if (array_key_exists('file_ids', $message) && is_array($message['file_ids']) && count($message['file_ids']) > 0) {
            $this->files = $message['file_ids'];
            $this->file = sprintf(
                'https://mm.imagefap.com/api/v4/files/%s/preview',
                $message['file_ids'][0]
            );
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getMsg(): string
    {
        return $this->msg;
    }

    public function isImage(): bool
    {
        if(isset($this->file))
            return true;

        return str_starts_with($this->msg, 'https://');
    }

    public function getImage(): string
    {
        return $this->file ?? $this->msg;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setTimeAgo(string $timeAgo): string
    {
        return $this->timeAgo = $timeAgo;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getMessage(): string
    {
        return $this->msg;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setPublicUrl(?string $publicUrl): void
    {
        $this->publicUrl = $publicUrl;
    }

    public function getPublicUrl(): ?string
    {
        return $this->publicUrl;
    }
}