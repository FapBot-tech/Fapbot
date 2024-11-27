<?php
declare(strict_types=1);


namespace App\Form;

use App\Entity\User;
use Doctrine\Common\Collections\Collection;


class UserDto {
    public Collection $channels;
    public string $username;
    public ?string $password;
    public string $role;
    public bool $sendWelcomeMessage = false;

    public static function createFromUser(User $user): self
    {
        $self = new self();

        $self->channels = $user->getChannels();
        $self->username = $user->getUserIdentifier();
        $self->role = match ($user->getRole()) {
            'ROLE_SUPER_ADMIN' => 'super_admin',
            'ROLE_ADMIN' => 'admin',
            'ROLE_CHAT_ADMIN' => 'chat_admin',
            'ROLE_CHAT_MOD' => 'chat_mod',
            'ROLE_USER' => 'user',
        };

        return $self;
    }

    /**
     * @return array
     */
    public function getDatabaseRoles(): array
    {
        return match ($this->role) {
            'super_admin' => ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_CHAT_ADMIN', 'ROLE_CHAT_MOD', 'ROLE_USER'],
            'admin' => ['ROLE_ADMIN', 'ROLE_CHAT_ADMIN', 'ROLE_CHAT_MOD', 'ROLE_USER'],
            'chat_admin' => ['ROLE_CHAT_ADMIN', 'ROLE_CHAT_MOD', 'ROLE_USER'],
            'chat_mod' => ['ROLE_CHAT_MOD', 'ROLE_USER'],
            'user' => ['ROLE_USER'],
        };
    }
}