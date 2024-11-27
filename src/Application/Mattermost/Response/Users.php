<?php
declare(strict_types=1);

namespace App\Application\Mattermost\Response;

use App\Application\Chat\Response\UsersInterface;


final class Users implements UsersInterface
{
    public array $users = [];

    public function __construct(array $users)
    {
        foreach ($users as $user) {
            $this->users[] = new User($user);
        }
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function getLength(): int
    {
        return count($this->users);
    }

    public function addUsers(array $users): void
    {
        $this->users = array_merge($this->users, $users);
    }
}