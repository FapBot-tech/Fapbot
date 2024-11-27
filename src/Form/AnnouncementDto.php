<?php
declare(strict_types=1);


namespace App\Form;

use App\Entity\Announcement;
use App\Form\Validation\ChannelNotBlank;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;


final class AnnouncementDto
{
    #[ChannelNotBlank]
    public Collection $channels;
    #[NotBlank]
    public string $text;
    public ?string $header = null;
    public ?string $links = null;
    public string $interval;

    public function toAnnouncement(): Announcement
    {
        return new Announcement($this->channels, $this->text, $this->header, $this->links, $this->interval);
    }

    public static function fromAnnouncement(Announcement $announcement): self
    {
        $self = new self();

        $self->channels = $announcement->getChannels();
        $self->header = $announcement->getHeader();
        $self->text = $announcement->getText();
        $self->links = $announcement->getLinks();
        $self->interval = $announcement->getSendInterval();

        return $self;
    }
}