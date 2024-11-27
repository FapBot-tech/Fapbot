<?php
declare(strict_types=1);


namespace App\Application\Console;

use App\Application\RocketChat\ChatMessage;
use App\Application\RocketChat\Connector;
use App\Application\RocketChat\Integration;
use App\Entity\LastExecute;
use App\Entity\Repository\LastExecuteRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


final class NotifyWhenUserOnline extends Command
{
    public const USERS_TO_NOTIFY_FOR = [
        'nieuwschierigpoesje',
        'kinkyslutgirl',
        'Guusje05',
        'kinky_eve',
        'CumdumpEmma',
        'aashia-muslim',
    ];

    private Connector $connector;
    private Integration $integration;
    private LastExecuteRepository $lastExecuteRepository;

    public function __construct(
        Connector $connector,
        Integration $integration,
        LastExecuteRepository $lastExecuteRepository,
        string $name = null
    ) {
        $this->connector = $connector;
        $this->integration = $integration;
        $this->lastExecuteRepository = $lastExecuteRepository;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName('users:online')
            ->setDescription('Inform me when certain users come online');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lastRun = $this->lastExecuteRepository->getLastExecuteForIdentifier(LastExecute::IDENTIFIER_USERS_ONLINE) ?? new LastExecute(LastExecute::IDENTIFIER_USERS_ONLINE);
        $output->writeln($lastRun?->getLastRunUtc()->format('Y-m-d H:i:s') ?? 'No last run found');

        $lastRunNotifications = json_decode($lastRun->getStore() ?? '[]');
        $onlineUsers = $this->integration->getUserList();

        $onlineUsernames = array_map(fn($user) => $user['username'], $onlineUsers->users);
        $intersect = array_values(array_intersect(self::USERS_TO_NOTIFY_FOR, $onlineUsernames));

        foreach ($intersect as $username) {
            if (in_array($username, $lastRunNotifications)) {
                continue;
            }

            $message = (new ChatMessage('@MyxR', sprintf('**Automatic message** @%s is online!', $username)))
                ->addColor('#00ff00');

            $this->connector->postMessage($message->getMessage());
            $output->writeln(sprintf('Notified for %s', $username));
        }

        foreach ($lastRunNotifications as $username) {
            if (in_array($username, $onlineUsernames)) {
                continue;
            }

            $message = (new ChatMessage('@MyxR', sprintf('**Automatic message** @%s is offline!', $username)))
                ->addColor('#ff0000');

            $this->connector->postMessage($message->getMessage());
            $output->writeln(sprintf('Notified for %s', $username));
        }

        $lastExecute = new LastExecute(LastExecute::IDENTIFIER_USERS_ONLINE);
        $lastExecute->setStore(json_encode($intersect));
        $this->lastExecuteRepository->save($lastExecute);

        return Command::SUCCESS;
    }
}