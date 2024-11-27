<?php
declare(strict_types=1);

namespace App\Application\RocketChat\Response;

use App\Application\Chat\Response\UsersInterface;


final class Users implements UsersInterface
{
    public array $users = [];

    public function __construct(array $users)
    {
        $this->users = $users;
    }

    public function addUsers(array $users): void
    {
        $this->users = array_merge($this->users, $users);
    }

    public function getFirstUser(): ?User
    {
        if (count($this->users) === 0)
            return null;

        return new User($this->users[0]);
    }

    public function getLength(): int
    {
        return count($this->users);
    }

    public function getUsers(): array
    {
        return $this->users;
    }
}