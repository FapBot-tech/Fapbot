<?php
declare(strict_types=1);

namespace App\Application\Console;

use App\Application\Chat\IntegrationInterface;
use App\Entity\Repository\MuteRepository;
use RectorPrefix202312\Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Nils Minten <nils@momoyoga.com>
 */
final class PurgeInactiveUsers extends Command {
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
            ->setName('users:purge')
            ->setDescription('Find and purge inactive users')
            ->addArgument('days', InputArgument::OPTIONAL, 'Number of days since last activity', 30)
            ->addOption('delete', 'd', InputOption::VALUE_REQUIRED, 'Actually delete the users', false);
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $allUsers = [];
        $page = 1;
        $activeBefore = new \DateTimeImmutable(sprintf('-%d days', $input->getArgument('days') ?? 30), new \DateTimeZone('UTC'));

        $output->writeln(sprintf('<info>Fetching users with last activity before %s</info>', $activeBefore->format('Y-m-d H:i:s')));

        do {
            $users = $this->integration->getUserList($page)
                ->getApiResponse()
                ->getUsers()
                ->getUsers();

            $page++;
            $allUsers = array_merge($allUsers, $users);

            $output->writeln(sprintf('Fetched %d users | <info>Page: %d</info> | <comment>Total: %d</comment>', count($users), $page, count($allUsers)));
        } while (!empty($users));

        $inactiveUsers = array_values(array_filter($allUsers, function($user) use ($activeBefore) {
            $lastActivity = $user->lastActivityAt;

            return $lastActivity !== null && $lastActivity < $activeBefore && !in_array($user->username, [
                'admin',
                'dutchb',
                'fapbot',
                'p0outs',
                'fisharino',
                'dutchbouncer',
                'inlinehockeyguy',
                'myxr',
                'robosexual-prototype',
                'southernsharpshooter'
            ]);
        }));

        $output->writeln(sprintf('Found %d inactive users', count($inactiveUsers)));

        foreach ($inactiveUsers as $user) {
            $output->writeln(
                sprintf('User %s is inactive since %s',
                    $user->username,
                    $user->lastActivityAt->format('Y-m-d H:i:s')
                )
            );

            if ($input->getOption('delete')) {
                $this->integration->deleteUser($user->id);
                $output->writeln(sprintf('<comment>Deleted user %s</comment>', $user->username));
            }
        }

        if ($input->getOption('delete') === false) {
            $output->writeln('<info>Use --delete to actually delete the users</info>');
        }

        return Command::SUCCESS;
    }
}