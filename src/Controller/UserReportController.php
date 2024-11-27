<?php
declare(strict_types=1);

namespace App\Controller;

use App\Application\Chat\IntegrationInterface;
use App\Entity\Repository\PageContentRepository;
use App\Entity\Repository\ReportRepository;
use App\Form\PageContentType;
use App\Form\ReportDto;
use App\Form\ReportType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


final class UserReportController extends AbstractController
{
    private FormFactoryInterface $formFactory;
    private ReportRepository $reportRepository;
    private IntegrationInterface $integration;
    private PageContentRepository $pageContentRepository;

    public function __construct(
        FormFactoryInterface $formFactory,
        ReportRepository     $reportRepository,
        IntegrationInterface $integration,
        PageContentRepository $pageContentRepository
    ) {
        $this->formFactory = $formFactory;
        $this->reportRepository = $reportRepository;
        $this->integration = $integration;
        $this->pageContentRepository = $pageContentRepository;
    }

    public function __invoke(Request $request): Response
    {
        $dto = new ReportDto();
        $form = $this->formFactory->create(ReportType::class, $dto);
        $form->handleRequest($request);

        $pageContent = $this->pageContentRepository->findByIdentifier('report_how_to');

        if ($form->isSubmitted() && $form->isValid()) {
            $report = $dto->toEntity($this->integration);

            $this->reportRepository->save($report);
            $this->integration->sendReport($report);

            $this->addFlash('success', 'Report submitted successfully.');
            return $this->redirectToRoute('chat_user_report');
        }

        return $this->render('reports/public_report.html.twig', [
            'form' => $form->createView(),
            'page_content' => $pageContent
        ]);
    }
}