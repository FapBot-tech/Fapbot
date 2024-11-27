<?php
declare(strict_types=1);


namespace App\Entity;


use App\Entity\Repository\BlockedUserRepository;
use App\Infrastructure\TimeRemainingFormatter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlockedUserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class BlockedUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 180)]
    private string $username;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $unblockDetected;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updated;

    public function __construct(string $username)
    {
        $this->username = $username;
        $this->created = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function isBocked(): bool
    {
        return $this->unblockDetected === null;
    }

    public function unblock(): void
    {
        $this->unblockDetected = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getTimeAgo(): string
    {
        return TimeRemainingFormatter::formatRemainingDays($this->created, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
    }

    public function getLastChecked(): string
    {
        if ($this->updated)
            return TimeRemainingFormatter::formatRemainingDays($this->updated, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));

        return TimeRemainingFormatter::formatRemainingDays($this->created, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
    }

    #[ORM\PreUpdate]
    public function setUpdated(): void
    {
        $this->updated = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}