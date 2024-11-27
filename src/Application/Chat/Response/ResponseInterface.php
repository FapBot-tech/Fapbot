<?php
declare(strict_types=1);

namespace App\Application\Chat\Response;


interface ResponseInterface
{
    public function getUser(): ?UserInterface;
    public function getSuggestions(): ?array;
    public function getMessages(): ?MessagesInterface;
    public function getUsers(): ?UsersInterface;
    public function isSuccess(): bool;

    public function getResponse(): string;
    public function getRequest(): ?string;
}