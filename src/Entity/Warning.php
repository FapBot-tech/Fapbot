<?php
declare(strict_types=1);


namespace App\Entity;

use App\Infrastructure\TimeRemainingFormatter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Entity\Repository\WarningRepository')]
#[ORM\HasLifecycleCallbacks]
class Warning {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'warnings')]
    private User $user;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $userName;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $channelId;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updated;

    public function __construct(
        User $user,
        ?string $userName,
        ?string $channelId,
        string $reason
    ) {
        $this->user = $user;
        $this->userName = $userName;
        $this->channelId = $channelId;
        $this->reason = $reason;
        $this->created = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    public function isForUser(): bool
    {
        return !str_contains($this->getUserName(), '#');
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getTimeAgo(): string
    {
        return TimeRemainingFormatter::formatRemainingDays($this->created, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
    }

    public function getChannelId(): ?string
    {
        return $this->channelId;
    }

    public function setUserName(?string $userName): void
    {
        $this->userName = $userName;
    }
}