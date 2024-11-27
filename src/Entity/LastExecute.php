<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Entity\Repository\ChannelRepository')]
class LastExecute
{
    public const IDENTIFIER_EXTRACT_MUTES = 'extract_mutes';
    public const IDENTIFIER_EXTRACT_REPORTS = 'extract_reports';
    public const IDENTIFIER_USERS_ONLINE = 'online_users';
    public const IDENTIFIER_MUTE_LOAD = 'mute_load';
    public const IDENTIFIER_EMAIL_SENT = 'email_sent';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $identifier;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $lastRun;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $store = null;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
        $this->lastRun = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLastRun(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->lastRun->format('Y-m-d H:i:s'), new DateTimeZone('UTC'));
    }

    public function getLastRunUtc(): DateTimeImmutable
    {
        return $this->getLastRun()->setTimezone(new DateTimeZone('UTC'));
    }

    public function setStore(string $store): void
    {
        $this->store = $store;
    }

    public function getStore(): ?string
    {
        return $this->store;
    }
}