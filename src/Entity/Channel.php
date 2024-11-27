<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Entity\Repository\ChannelRepository')]
class Channel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $identifier;

    #[ORM\Column(type: 'string', length: 255)]
    private string $Name;

    #[ORM\ManyToMany(targetEntity: 'App\Entity\Mute', mappedBy: 'channels')]
    #[ORM\OrderBy(['id' => 'DESC'])]
    protected Collection $mutes;

    #[ORM\ManyToMany(targetEntity: 'App\Entity\User', mappedBy: 'channels')]
    #[ORM\OrderBy(['id' => 'DESC'])]
    protected Collection $users;

    #[ORM\ManyToMany(targetEntity: 'Announcement', mappedBy: 'channels')]
    #[ORM\OrderBy(['id' => 'DESC'])]
    protected Collection $announcements;

    public function __construct(string $name, string $identifier)
    {
        $this->Name = $name;
        $this->identifier = $identifier;
        $this->mutes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(string $Name): self
    {
        $this->Name = $Name;

        return $this;
    }
}
