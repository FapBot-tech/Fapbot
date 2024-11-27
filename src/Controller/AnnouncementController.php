<?php
declare(strict_types=1);


namespace App\Controller;

use App\Application\Chat\IntegrationInterface;
use App\Entity\Announcement;
use App\Entity\Repository\AnnouncementRepository;
use App\Form\AnnouncementDto;
use App\Form\AnnouncementType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;


final class AnnouncementController extends AbstractController
{
    private AnnouncementRepository $announcementRepository;
    private Security $security;
    private FormFactoryInterface $formFactory;
    private IntegrationInterface $integration;

    public function __construct(
        AnnouncementRepository $announcementRepository,
        Security $security,
        FormFactoryInterface $formFactory,
        IntegrationInterface $integration
    ) {
        $this->announcementRepository = $announcementRepository;
        $this->security = $security;
        $this->formFactory = $formFactory;
        $this->integration = $integration;
    }

    public function listAnnouncements(Request $request): Response
    {
        $announcements = $this->announcementRepository->findAll();

        return $this->render('/announcement/index.html.twig', [
            'announcements' => $announcements,
        ]);
    }

    #[IsGranted('ROLE_CHAT_ADMIN')]
    public function create(Request $request): Response
    {
        $user = $this->security->getUser();

        $dto = new AnnouncementDto();
        $form = $this->formFactory->create(AnnouncementType::class, $dto, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $announcement = $dto->toAnnouncement();
            $this->announcementRepository->save($announcement);

            $this->addFlash('success', 'Announcement has been added');

            sleep(2);
            return $this->redirectToRoute('announcements');
        }

        return $this->render('/announcement/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_CHAT_ADMIN')]
    public function edit(Request $request, Announcement $announcement): Response
    {
        $user = $this->security->getUser();

        $dto = AnnouncementDto::fromAnnouncement($announcement);
        $form = $this->formFactory->create(AnnouncementType::class, $dto, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $announcement->updateFromDto($dto);
            $this->announcementRepository->save($announcement);

            $this->addFlash('success', 'Announcement has been updated');

            sleep(1);
            return $this->redirectToRoute('announcements');
        }

        return $this->render('/announcement/edit.html.twig', [
            'form' => $form->createView(),
            'announcement' => $announcement

        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    public function delete(Announcement $announcement): RedirectResponse
    {
        $this->announcementRepository->delete($announcement);
        $this->addFlash('success', 'Announcement has been deleted');

        return $this->redirectToRoute('announcements');
    }

    #[IsGranted('ROLE_ADMIN')]
    public function sendNow(Announcement $announcement): RedirectResponse
    {
        $this->integration->announcement($announcement);
        $this->addFlash('success', 'Announcement has been sent');

        return $this->redirectToRoute('announcements');
    }
}