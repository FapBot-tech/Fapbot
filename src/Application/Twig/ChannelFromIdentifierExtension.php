<?php
declare(strict_types=1);

namespace App\Application\Twig;

use App\Entity\Repository\ChannelRepository;
use App\Entity\Repository\UserRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


final class ChannelFromIdentifierExtension extends AbstractExtension
{
    private ChannelRepository $channelRepository;

    public function __construct(ChannelRepository $channelRepository)
    {
        $this->channelRepository = $channelRepository;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('channel_name', [$this, 'getChannelName']),
        ];
    }

    public function getChannelName(string $identifier): string
    {
        $channel = $this->channelRepository->findByIdentifier($identifier);

        return $channel?->getName() ?? 'Unknown channel';
    }
}