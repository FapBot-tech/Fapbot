<?php
declare(strict_types=1);


namespace App\Entity;

use App\Form\AnnouncementDto;
use App\Infrastructure\TimeRemainingFormatter;
use DateInterval;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Entity\Repository\AnnouncementRepository')]
class Announcement
{
    public const INTERVALS = [
        '15 minutes' => '+15 minutes',
        '30 minutes' => '+30 minutes',
        '45 minutes' => '+45 minutes',
        '1 hour' => '+1 hour',
        '1.5 hours' => '+90 minutes',
        '2 hours' => '+2 hours',
        '2.5 hours' => '+2.5 hours',
        '3 hours' => '+3 hours',
        '3.5 hours' => '+3.5 hours',
        '4 hours' => '+4 hours',
        '4.5 hours' => '+4.5 hours',
        '5 hours' => '+5 hours',
        '5.5 hours' => '+5.5 hours',
        '6 hours' => '+6 hours',
        '6.5 hours' => '+6.5 hours',
        '7 hours' => '+7 hours',
        '7.5 hours' => '+7.5 hours',
        '8 hours' => '+8 hours',
        '8.5 hours' => '+8.5 hours',
        '9 hours' => '+9 hours',
        '9.5 hours' => '+9.5 hours',
        '10 hours' => '+10 hours',
        '10.5 hours' => '+10.5 hours',
        '12 hours' => '+12 hours',
        '24 hours' => '+24 hours'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\JoinTable(name: 'announcement_channels')]
    #[ORM\ManyToMany(targetEntity: 'Channel', inversedBy: 'announcements', cascade: ['persist'])]
    private Collection $channels;

    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $header;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $links;

    #[ORM\Column(type: 'string')]
    private string $sendInterval;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $send;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updated;

    public function __construct(
        Collection $channels,
        string $text,
        ?string $header,
        ?string $links,
        ?string $interval
    ) {
        $this->channels = $channels;
        $this->text = $text;
        $this->header = $header;
        $this->links = $links;
        $this->sendInterval = $interval ?? self::INTERVALS['+30 minutes'];
        $this->setSend();
        $this->created = new \DateTimeImmutable('now');
    }

    public function updateFromDto(AnnouncementDto $dto): void
    {
        $this->channels = $dto->channels;
        $this->header = $dto->header;
        $this->text = $dto->text;
        $this->links = $dto->links;
        $this->sendInterval = $dto->interval;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /** @return Channel[] */
    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getHeader(): ?string
    {
        return $this->header;
    }

    public function getLinks(): ?string
    {
        return $this->links;
    }

    #[ORM\PreUpdate]
    public function setUpdated(): void
    {
        $this->updated = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
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

    public function getSendInterval(): string
    {
        return $this->sendInterval;
    }

    public function updateSend(): void
    {
        $this->send = $this->send->modify($this->sendInterval);
        $this->updated = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function sendsIn(): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return TimeRemainingFormatter::formatRemainingTime($now, $this->send);
    }

    private function setSend(): void
    {
        $send = (new \DateTime('now', new \DateTimeZone('UTC')))->modify($this->sendInterval);

        $second = $send->format("s");
        if($second > 0)
            $send->add(new DateInterval("PT".(60-$second)."S"));

        $minute = $send->format("i");
        $minute = $minute % 15;

        if($minute != 0) {
            $diff = 15 - $minute;
            $send->add(new DateInterval("PT".$diff."M"));
        }

        $this->send = \DateTimeImmutable::createFromInterface($send);
    }
}