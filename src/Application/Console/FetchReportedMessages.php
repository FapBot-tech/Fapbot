<?php
declare(strict_types=1);


namespace App\Application\Console;

use App\Application\Chat\IntegrationInterface;
use App\Application\Mattermost\MatterMostIntegration;
use App\Application\RocketChat\Response\Report;
use App\Application\RocketChat\Response\Reports;
use App\Entity\LastExecute;
use App\Entity\Repository\LastExecuteRepository;
use DateTimeZone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


final class FetchReportedMessages extends Command
{
    private LastExecuteRepository $lastExecuteRepository;
    private IntegrationInterface $integration;

    public function __construct(
        LastExecuteRepository $lastExecuteRepository,
        IntegrationInterface $integration,
        string $name = null
    ) {
        $this->lastExecuteRepository = $lastExecuteRepository;
        $this->integration = $integration;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName('reports:fetch')
            ->setDescription('Attempt to load in reported messages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->integration instanceof MatterMostIntegration)
            return Command::SUCCESS;

        $lastRun = $this->lastExecuteRepository->getLastExecuteForIdentifier(LastExecute::IDENTIFIER_EXTRACT_REPORTS);
        $lastExecute = new LastExecute(LastExecute::IDENTIFIER_EXTRACT_REPORTS);

        $output->writeln($lastRun?->getLastRunUtc()->format('Y-m-d H:i:s') ?? 'No last run found');

        $response = $this->integration->getReportedMessages($lastRun?->getLastRunUtc() ?? new \DateTimeImmutable('now', new DateTimeZone('UTC')));

        if ($response->success === false) {
            $output->writeln('Failed to fetch reports');

            return Command::SUCCESS;
        }
        $this->lastExecuteRepository->save($lastExecute);

        $reports = $response->reports;
        $output->writeln('Found ' . count($reports->reports) . ' reports');

        /** @var Reports $report */
        foreach ($reports->reports as $report) {
            /** @var Report $report */
            $userReports = $this->integration->getUserReports($report->userId);
            $report->messages = $userReports->messages->messages;

            foreach ($report->messages as $message) {

                $this->integration->shareReportInChat($report, $message);
            }
        }

        return Command::SUCCESS;
    }
}