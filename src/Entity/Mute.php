<?php
declare(strict_types=1);


namespace App\Entity;

use App\Infrastructure\TimeRemainingFormatter;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Entity\Repository\MuteRepository')]
#[ORM\HasLifecycleCallbacks]
class Mute {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\JoinTable(name: 'mute_channels')]
    #[ORM\ManyToMany(targetEntity: 'Channel', inversedBy: 'mutes', cascade: ['persist'])]
    private Collection $channels;

    #[ORM\ManyToOne(targetEntity: 'User', cascade: ['persist'], inversedBy: 'mutes')]
    private User $user;

    #[ORM\Column(type: 'string')]
    private string $userName;

    #[ORM\Column(type: 'string')]
    private string $chatUserId;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $endTime;

    #[ORM\Column(type: 'boolean')]
    private bool $active;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updated;

    public function __construct(User $user, Collection $channels, string $userName, string $userId, string $reason, \DateTime $endTime) {
        $this->user = $user;
        $this->channels = $channels;
        $this->userName = $userName;
        $this->chatUserId = $userId;
        $this->reason = $reason;
        $this->endTime = $endTime;
        $this->active = true;

        $this->created = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection<Channel>
     */
    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function getChannelsString(): string
    {
        $channelCount = count($this->channels);
        if ($channelCount >= 35)
            return sprintf("(almost) all channels (%d/46)", $channelCount);

        $output = '';
        foreach ($this->channels as $channel) {
            if ($channel !== $this->channels->last())
                $output .= '#' . $channel->getName() . ', ';
            else
                $output .= '#' . $channel->getName() . '';
        }

        return $output;
    }

    /**
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userName;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @return \DateTime
     */
    public function getEndTime(): \DateTime
    {
        return $this->endTime;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setUnmuted(): void
    {
        $this->endTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->active = false;
    }

    public function getDuration(): string
    {
        return TimeRemainingFormatter::formatRemainingTime($this->created, $this->endTime);
    }

    public function getDurationInDays(): int
    {
        $dateDiff = $this->created->diff($this->endTime);
        $days = $dateDiff->format("%a");

        if ($dateDiff->h == 23)
            $days++;

        return $days;
    }

    public function timeLeft(): string
    {
        return TimeRemainingFormatter::formatRemainingTime(new \DateTimeImmutable('now', new \DateTimeZone('UTC')), $this->endTime);
    }

    public function getTimeAgo(): string
    {
        return TimeRemainingFormatter::formatRemainingDays($this->created, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    #[ORM\PreUpdate]
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function removeChannel(Channel $channel): void
    {
        $this->channels->removeElement($channel);
    }

    public function getUserId(): string
    {
        return $this->chatUserId;
    }
}