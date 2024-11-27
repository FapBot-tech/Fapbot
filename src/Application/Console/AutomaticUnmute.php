<?php
declare(strict_types=1);

namespace App\Application\Console;

use App\Application\Chat\IntegrationInterface;
use App\Application\Mattermost\MatterMostIntegration;
use App\Entity\Mute;
use App\Entity\Repository\MuteRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class AutomaticUnmute extends Command {

    private MuteRepository $muteRepository;
    private IntegrationInterface $integration;

    public function __construct(
        MuteRepository $muteRepository,
        IntegrationInterface $integration,
        string $name = null
    ) {
        $this->muteRepository = $muteRepository;
        $this->integration = $integration;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName('mutes:unmute')
            ->setDescription('Unmute expired mutes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $unmutes = $this->muteRepository->findReadyForUnmute();

        /** @var Mute $unmute */
        foreach ($unmutes as $unmute) {
            $unmute->setUnmuted();
            $this->integration->unmute($unmute);

            $this->muteRepository->save($unmute);

            $output->writeln($unmute->getUserName() . ' has been unmuted');
        }

        return Command::SUCCESS;
    }
}