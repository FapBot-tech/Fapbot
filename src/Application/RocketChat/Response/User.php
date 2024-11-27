<?php
declare(strict_types=1);

namespace App\Application\RocketChat\Response;

use App\Application\Chat\Response\UserInterface;

final class User implements UserInterface {
    public string $_id;
    public string $name;
    public string $username;
    public ?string $email = null;

    public string $type;
    public string $status;
    public string $active;
    public float $utcOffset;
    public string $profile = '';
    public string $gender = 'Male';
    public bool $usernameCorrected = false;

    public function __construct(array $user)
    {
        $this->_id = $user['_id'];
        $this->username = $user['username'];

        if(array_key_exists('name', $user))
            $this->name = $user['name'];

        if(array_key_exists('emails', $user))
            $this->email = $user['emails'][0]['address'];

        if(array_key_exists('type', $user))
            $this->type = $user['type'];

        if(array_key_exists('status', $user))
            $this->status = $user['status'];

        if(array_key_exists('active', $user))
            $this->active = $user['active'] ? 'true' : 'false';

        if(array_key_exists('utcOffset', $user))
            $this->utcOffset = $user['utcOffset'];

        if (array_key_exists('customFields', $user)) {
            $this->profile = $user['customFields']['Profile'];
            $this->gender = $user['customFields']['Gender'];
        }

    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function isActive(): bool
    {
        return $this->active === 'true';
    }

    public function isUsernameCorrected(): bool
    {
        return $this->usernameCorrected;
    }

    public function setCorrectedUsername(bool $corrected): void
    {
        $this->usernameCorrected = $corrected;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getEmail(): string
    {
        return $this->email ?? "";
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
