<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\PageContent;
use App\Entity\Repository\PageContentRepository;
use App\Form\PageContentDto;
use App\Form\PageContentType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


final class PageContentController extends AbstractController
{
    private PageContentRepository $pageContentRepository;
    private FormFactoryInterface $formFactory;
    private Security $security;

    public function __construct(
        PageContentRepository $pageContentRepository,
        FormFactoryInterface $formFactory,
        Security $security
    ) {
        $this->pageContentRepository = $pageContentRepository;
        $this->formFactory = $formFactory;
        $this->security = $security;
    }

    public function index(): Response
    {
        $pageContents = $this->pageContentRepository->findAll();
        $addedIdentifiers = array_map(fn(PageContent $pageContent) => $pageContent->getIdentifier(), $pageContents);
        $possibleIdentifiers = array_diff(PageContentType::IDENTIFIERS, $addedIdentifiers);

        return $this->render('page_content/index.html.twig', [
            'pageContents' => $pageContents,
            'canAdd' => count($possibleIdentifiers) > 0
        ]);
    }

    public function create(Request $request): Response
    {
        $pageContents = $this->pageContentRepository->findAll();
        $addedIdentifiers = array_map(fn(PageContent $pageContent) => $pageContent->getIdentifier(), $pageContents);

        $dto = new PageContentDto();
        $form = $this->formFactory->create(PageContentType::class, $dto, [
            'added_identifiers' => $addedIdentifiers
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pageContent = $dto->toEntity($this->security->getUser());
            $this->pageContentRepository->save($pageContent);

            $this->addFlash('success', 'Page content has been created');

            return $this->redirectToRoute('page_content_index');
        }

        return $this->render('page_content/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function update(Request $request, PageContent $pageContent): Response
    {
        $pageContents = $this->pageContentRepository->findAll();
        $addedIdentifiers = array_map(fn(PageContent $pageContent) => $pageContent->getIdentifier(), $pageContents);

        $dto = PageContentDto::fromEntity($pageContent);
        $form = $this->formFactory->create(PageContentType::class, $dto, [
            'added_identifiers' => $addedIdentifiers
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pageContent->update($dto->content, $this->security->getUser());
            $this->pageContentRepository->save($pageContent);

            $this->addFlash('success', 'Page content has been updated');

            return $this->redirectToRoute('page_content_index');
        }

        return $this->render('page_content/edit.html.twig', [
            'form' => $form->createView(),
            'pageContent' => $pageContent
        ]);
    }

    public function delete(PageContent $pageContent): Response
    {
        $this->pageContentRepository->delete($pageContent);

        $this->addFlash('success', 'Page content has been deleted');

        return $this->redirectToRoute('page_content_index');
    }
}