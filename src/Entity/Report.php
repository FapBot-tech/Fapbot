<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Entity\Repository\ReportRepository')]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
#[ORM\HasLifecycleCallbacks]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $reporterName;

    #[ORM\Column(type: 'string')]
    private string $reporterChatUserId;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reportedMessageLink;

    #[ORM\Column(type: 'string')]
    private string $reportedName;

    #[ORM\Column(type: 'string')]
    private string $reportedChatUserId;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deleted;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updated;

    public function __construct(
        string $reporterName,
        string $reporterChatUserId,
        string $reason,
        ?string $reportedMessageLink,
        string $reportedName,
        string $reportedChatUserId
    ) {
        $this->reporterName = $reporterName;
        $this->reporterChatUserId = $reporterChatUserId;
        $this->reason = $reason;
        $this->reportedMessageLink = $reportedMessageLink;
        $this->reportedName = $reportedName;
        $this->reportedChatUserId = $reportedChatUserId;

        $this->created = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getReporterName(): string
    {
        return $this->reporterName;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getReportedMessageLink(): ?string
    {
        return $this->reportedMessageLink;
    }

    public function getReportedName(): string
    {
        return $this->reportedName;
    }
}