<?php
declare(strict_types=1);


namespace App\Application\Console;

use App\Application\Chat\IntegrationInterface;
use App\Entity\Repository\AnnouncementRepository;
use App\Entity\Repository\ApiLogRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


final class PurgeApiLogs extends Command
{
    private ApiLogRepository $apiLogRepository;

    public function __construct(
        ApiLogRepository $apiLogRepository,
        string $name = null
    ) {
        $this->apiLogRepository = $apiLogRepository;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName('api_log:purge')
            ->setDescription('Delete old api logs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Purging api logs...');
        $this->apiLogRepository->purge();
        $output->writeln('Api logs purged');

        return Command::SUCCESS;
    }
}