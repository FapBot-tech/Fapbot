<?php
declare(strict_types=1);

namespace App\Application\Chat\Response;


interface UsersInterface
{
    public function getUsers(): array;
    public function getLength(): int;
    public function addUsers(array $users): void;
}