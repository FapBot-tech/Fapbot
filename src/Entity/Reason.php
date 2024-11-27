<?php
declare(strict_types=1);


namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Entity\Repository\ReasonRepository')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'cache.app')]
class Reason
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 140)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updated;

    public function __construct(string $name, string $reason)
    {
        $this->name = $name;
        $this->reason = $reason;

        $this->created = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function update(string $name, string $reason)
    {
        $this->name = $name;
        $this->reason = $reason;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    #[ORM\PreUpdate]
    public function setUpdatedValue()
    {
        $this->updated = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}