<?php
declare(strict_types=1);


namespace App\Controller;

use App\Entity\Repository\ApiLogRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


final class LoggingController extends AbstractController
{
    private ApiLogRepository $apiLogRepository;

    public function __construct(ApiLogRepository $apiLogRepository)
    {
        $this->apiLogRepository = $apiLogRepository;
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function __invoke(): Response
    {
        $successCount = $this->apiLogRepository->getSuccessfulCount();
        $failCount = $this->apiLogRepository->getFailureCount();
        $successPercentage = $successCount / ($successCount + $failCount) * 100;

        $successLogs = $this->apiLogRepository->getSuccessLogs();
        $failLogs = $this->apiLogRepository->getFailLogs();

        return $this->render('logs/index.html.twig', [
            'successCount' => $successCount,
            'failCount' => $failCount,
            'successPercentage' => $successPercentage,
            'successLogs' => $successLogs,
            'failLogs' => $failLogs
        ]);
    }
}