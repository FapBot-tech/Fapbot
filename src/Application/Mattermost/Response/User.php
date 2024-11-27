<?php
declare(strict_types=1);

namespace App\Application\Mattermost\Response;

use App\Application\Chat\Response\UserInterface;
use DateTimeImmutable;


final class User implements UserInterface
{
    public string $id;
    public DateTimeImmutable $createdAt;
    public ?DateTimeImmutable $updatedAt;
    public ?DateTimeImmutable $deletedAt;
    public string $username;
    public string $authData;
    public string $authService;
    public string $email;
    public string $nickname;
    public string $firstName;
    public string $lastName;
    public string $locale;
    public bool $usernameCorrected = false;
    public ?string $status = null;

    public function __construct(array $user)
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->id = $user['id'];
        $this->createdAt = DateTimeImmutable::createFromMutable($date->setTimestamp((int)floor($user['create_at'] / 1000)));
        $this->updatedAt = isset($user['update_at']) ? DateTimeImmutable::createFromMutable($date->setTimestamp((int)floor($user['update_at'] / 1000))) : null;
        $this->deletedAt = isset($user['delete_at']) ? DateTimeImmutable::createFromMutable($date->setTimestamp((int)floor($user['delete_at'] / 1000))) : null;
        $this->username = $user['username'];
        $this->authData = $user['auth_data'];
        $this->authService = $user['auth_service'];
        $this->email = $user['email'];
        $this->nickname = $user['nickname'];
        $this->firstName = $user['first_name'];
        $this->lastName = $user['last_name'];
        $this->locale = $user['locale'];
    }

    public function setCorrectedUsername(bool $corrected): void
    {
        $this->usernameCorrected = $corrected;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isActive(): bool
    {
        return $this->deletedAt->diff(new DateTimeImmutable('now', new \DateTimeZone('UTC')))->y > 20;
    }

    public function isUsernameCorrected(): bool
    {
        return $this->usernameCorrected;
    }

    public function getStatus(): string
    {
        return $this->status ?? ($this->isActive() ? 'active' : 'inactive');
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setStatus(string $status): void
    {
        $this->status = match ($status) {
            'dnd' => 'busy',
            default => $status
        };
    }
}