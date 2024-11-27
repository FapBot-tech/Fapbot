<?php

namespace App\Entity;

use App\Entity\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $username;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\JoinTable(name: 'mute_users')]
    #[ORM\ManyToMany(targetEntity: 'Channel', inversedBy: 'users', cascade: ['persist'])]
    #[ORM\OrderBy(['Name' => 'ASC'])]
    private Collection $channels;

    #[ORM\OneToMany(targetEntity: 'Mute', mappedBy: 'user')]
    private Collection $mutes;

    #[ORM\OneToMany(targetEntity: 'Warning', mappedBy: 'user')]
    private Collection $warnings;

    public function __construct(string $username, array $roles = ['ROLE_USER']) {
        $this->username = $username;
        $this->roles = $roles;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }


    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getRole(): string
    {
        if ($this->isSuperAdmin())
            return 'ROLE_SUPER_ADMIN';

        if ($this->isAdmin())
            return 'ROLE_ADMIN';

        if ($this->isChatAdmin())
            return 'ROLE_CHAT_ADMIN';

        if ($this->isChatModerator())
            return 'ROLE_CHAT_MOD';

        return 'ROLE_USER';
    }

    public function getRoleString(): string
    {
        return match ($this->getRole()) {
            'ROLE_SUPER_ADMIN' => 'Super admin',
            'ROLE_ADMIN' => 'FapBot admin',
            'ROLE_CHAT_ADMIN' => 'Chat admin',
            'ROLE_CHAT_MOD' => 'Chat moderator',
            'ROLE_USER' => 'Moderator',
        };
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function isChatModerator(): bool
    {
        return in_array('ROLE_CHAT_MOD', $this->roles);
    }

    public function isChatAdmin(): bool
    {
        return in_array('ROLE_CHAT_ADMIN', $this->roles);
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles);
    }

    public function isSuperAdmin(): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $this->roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function setChannels(Collection $channels): void
    {
        $this->channels = $channels;
    }

    public function getMutes(): Collection
    {
        return $this->mutes;
    }

    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    public function isHigherRankThan(User $user): bool
    {
        if ($user->getId() === $this->getId())
            return true;

        if ($this->isSuperAdmin())
            return true;

        if ($user->getRole() === $this->getRole())
            return false;

        if (in_array($user->getRole(), $this->getRoles()))
            return true;

        return false;
    }

    public function hasAccessToAllChannels(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isChatAdmin() || $this->isChatModerator();
    }

    public function hasAccessToChannel(Collection|array $channels): bool
    {
        if ($this->hasAccessToAllChannels())
            return true;

        foreach ($channels as $channel) {
            if ($this->channels->contains($channel))
                return true;
        }

        return false;
    }
}
