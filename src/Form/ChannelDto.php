<?php
declare(strict_types=1);


namespace App\Form;

use App\Entity\Channel;


class ChannelDto {
    public string $name;
    public string $identifier;

    public static function fromChannel(Channel $channel): self
    {
        $self = new self();

        $self->name= $channel->getName();
        $self->identifier = $channel->getIdentifier();

        return $self;
    }

    public function updateChannel(Channel $channel): Channel
    {
        $channel->setName($this->name);
        $channel->setIdentifier($this->identifier);

        return $channel;
    }
}