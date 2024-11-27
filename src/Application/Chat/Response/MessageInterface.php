<?php
declare(strict_types=1);

namespace App\Application\Chat\Response;

interface MessageInterface
{
    public function getId(): string;
    public function getChannelId(): string;
    public function getMsg(): string;
    public function isImage(): bool;
    public function getImage(): string;
    public function getType(): string;
    public function getCreated(): \DateTimeImmutable;
    public function setTimeAgo(string $timeAgo): string;
    public function getUserId(): string;
}