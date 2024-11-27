<?php
declare(strict_types=1);

namespace App\Controller;

use App\Application\Chat\IntegrationInterface;
use App\Application\Mattermost\MatterMostIntegration;
use App\Application\RocketChat\Response\Report;
use App\Application\RocketChat\Response\Reports;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


final class ReportsController extends AbstractController
{
    private IntegrationInterface $integration;
    private Security $security;

    public function __construct(
        IntegrationInterface $integration,
        Security $security
    ) {

        $this->integration = $integration;
        $this->security = $security;
    }

    public function index(Request $request): Response
    {
        if ($this->integration instanceof MatterMostIntegration)
            return $this->render('feature_not_supported.twig');

        $start = new DateTimeImmutable('-1 week', new \DateTimeZone('UTC'));
        $end = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $reports = $this->integration->getReportedMessages($start, $end)->reports;

        /** @var Reports $report */
        foreach ($reports->reports as $report) {
            /** @var Report $report */
            $userReports = $this->integration->getUserReports($report->userId);
            $report->messages = $userReports->messages->messages;

            foreach ($report->messages as $message) {
                $messageReports = $this->integration->getMessageReports($message->_id)->reports;

                $message->reports = $messageReports->reports;
            }
        }

        return $this->render('reports/index.html.twig', [
            'reports' => $reports
        ]);
    }

    public function dismiss(string $userId): Response
    {
        if ($this->integration instanceof MatterMostIntegration)
            return $this->render('feature_not_supported.twig');

        $response = $this->integration->dismissUserReports($userId);

        if ($response->success === false) {
            $this->addFlash('error', $response->response);
        } else {
            $this->addFlash('success', 'Reports dismissed for user.');
        }

        return $this->redirectToRoute('reports');
    }

    public function delete(Request $request, string $userId): Response
    {
        if ($this->integration instanceof MatterMostIntegration)
            return $this->render('feature_not_supported.twig');

        $response = $this->integration->deleteReportedMessages($userId);

        if ($response->success === false) {
            $this->addFlash('error', 'Could not delete users messages.');
        } else {
            $this->addFlash('success', 'Deleted the messages for user.');
        }

        if ($request->query->has('mute'))
            return $this->redirectToRoute('mute_create', ['username' => $request->query->get('mute')]);

        if ($request->query->has('warn'))
            return $this->redirectToRoute('warning_create', ['username' => $request->query->get('warn')]);

        return $this->redirectToRoute('reports');
    }
}