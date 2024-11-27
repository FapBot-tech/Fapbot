<?php
declare(strict_types=1);

namespace App\Application\RocketChat\Response;

use App\Application\Chat\Response\MessageInterface;

class Message implements MessageInterface {
    public const TYPE = 'rocket';

    public string $_id;
    public string $id;
    public string $rid;
    public ?string $msg = '';
    public string $ts;
    public ?User $u;
    public string $_updatedAt;
    public ?string $file;
    public ?string $fileUrl;
    /** @var Report[]|null $reports */
    public ?array $reports;
    public ?string $timeAgo;
    public bool $isDeleted = false;

    public function __construct(array $message)
    {
        try {
            if (array_key_exists('message', $message))
                $message = $message['message'];

            $this->id = $this->_id = $message['_id'];
            $this->rid = $message['rid'];

            if(array_key_exists('_updatedAt', $message))
                $this->_updatedAt = $message['_updatedAt'];

            if(array_key_exists('ts', $message))
                $this->ts = $message['ts'];

            if(array_key_exists('file', $message) && array_key_exists('name', $message['file'])) {
                $this->file = $message['file']['name'];
            }

            if (array_key_exists('attachments',  $message) && is_array($message['attachments'])) {
                try {
                    $attachment = $message['attachments'][0];

                    if (is_array($attachment)) {
                        if (array_key_exists('image_url', $attachment)) {
                            $this->fileUrl = 'https://chat.imagefap.com' . $message['attachments'][0]['image_url'];
                        }
                        if (array_key_exists('description', $attachment)) {
                            $this->msg = $attachment['description'];
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore
                }
            }

            if(array_key_exists('u', $message))
                $this->u = new User($message['u']);

            if(array_key_exists('msg', $message) && $message['msg'] !== '')
                $this->msg = $message['msg'];


            $this->isDeleted = array_key_exists('editedBy', $message) && $message['editedBy']['username'] !== $message['u']['username'];
        } catch (\Exception $e) {
            dd($message, $e);
        }
    }

    public function isImage(): bool
    {
        if(isset($this->fileUrl))
            return true;

        return str_starts_with($this->msg, 'https://');
    }

    public function getImage(): string
    {
        if(isset($this->fileUrl))
            return $this->fileUrl;

        return $this->msg;
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function getChannelId(): string
    {
        return $this->rid;
    }

    public function getMsg(): string
    {
        return $this->msg;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->ts, new \DateTimeZone('UTC'));
    }

    public function setTimeAgo(string $timeAgo): string
    {
        return $this->timeAgo = $timeAgo;
    }

    public function getUserId(): string
    {
        return $this->u->_id;
    }
}