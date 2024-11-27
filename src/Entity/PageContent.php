<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Entity\Repository\PageContentRepository')]
#[ORM\HasLifecycleCallbacks]
class PageContent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: 'User', cascade: ['persist'])]
    private User $creator;

    #[ORM\ManyToOne(targetEntity: 'User', cascade: ['persist'])]
    #[Orm\JoinColumn(nullable: true)]
    private ?User $editor = null;

    #[ORM\Column(type: 'string')]
    private string $identifier;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updated = null;

    public function __construct(User $creator, string $identifier, string $content)
    {
        $this->creator = $creator;
        $this->identifier = $identifier;
        $this->content = $content;
        $this->created = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function update(string $content, User $editor): void
    {
        $this->content = $content;
        $this->editor = $editor;
        $this->updated = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreator(): User
    {
        return $this->creator;
    }

    public function getEditor(): ?User
    {
        return $this->editor;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function getUpdated(): DateTimeImmutable
    {
        return $this->updated;
    }

    #[ORM\PreUpdate]
    public function setUpdatedValue(): void
    {
        $this->updated = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}