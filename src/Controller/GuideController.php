<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Repository\PageContentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


final class GuideController extends AbstractController
{
    private PageContentRepository $contentRepository;

    public function __construct(
        PageContentRepository $contentRepository
    ) {
        $this->contentRepository = $contentRepository;
    }

    public function getContent(string $identifier): Response
    {
        $pageContent = $this->contentRepository->findByIdentifier($identifier);

        if ($pageContent === null)
            return $this->redirectToRoute('guide_content', ['identifier' => 'guide_index']);

        return $this->render('guide/content.html.twig', [
            'pageContent' => $pageContent
        ]);
    }
}