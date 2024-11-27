<?php
declare(strict_types=1);


namespace App\Application\Console;

use App\Application\Chat\IntegrationInterface;
use App\Entity\Repository\AnnouncementRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


final class AnnouncementMessage extends Command
{
    private AnnouncementRepository $announcementRepository;
    private IntegrationInterface $integration;

    public function __construct(
        AnnouncementRepository $announcementRepository,
        IntegrationInterface $integration,
        string $name = null
    ) {
        $this->announcementRepository = $announcementRepository;
        $this->integration = $integration;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName('announcements:announce')
            ->setDescription('Send scheduled announcements');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $announcements = $this->announcementRepository->findAllThatShouldBeSent();

        foreach ($announcements as $announcement) {
            $announcement->updateSend();

            $this->announcementRepository->save($announcement);

            $this->integration->announcement($announcement);
            $output->writeln(sprintf('Announcement send for channels: %s', $announcement->getChannelsString()));
        }


        return Command::SUCCESS;
    }
}