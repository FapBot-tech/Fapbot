<?php
declare(strict_types=1);

namespace App\Form;

use App\Application\Chat\Response\UserInterface;


final class DeactivateUserDto
{
    public string $userId;
    public bool $active;
    public ?string $reason = null;

    public static function createFromUser(UserInterface $user): self
    {
        $self = new self();

        $self->userId = $user->getId();
        $self->active = !$user->isActive();

        return $self;
    }
}