<?php
declare(strict_types=1);


namespace App\Controller;

use App\Application\Chat\IntegrationInterface;
use App\Application\Mattermost\Connector;
use App\Entity\Repository\WarningRepository;
use App\Entity\User;
use App\Entity\Warning;
use App\Form\SearchDto;
use App\Form\SearchType;
use App\Form\WarningDto;
use App\Form\WarningType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;


class WarningController extends AbstractController {
    private WarningRepository $warningRepository;
    private FormFactoryInterface $formFactory;
    private Security $security;
    private IntegrationInterface $integration;
    private Connector $connector;

    public function __construct(
        WarningRepository $warningRepository,
        FormFactoryInterface $formFactory,
        Security $security,
        IntegrationInterface $integration,
        Connector $connector
    ) {
        $this->warningRepository = $warningRepository;
        $this->formFactory = $formFactory;
        $this->security = $security;
        $this->integration = $integration;
        $this->connector = $connector;
    }

    public function list(Request $request): Response
    {
        $warnings = $this->warningRepository->findMostRecent();

        $dto = SearchDto::fromRequest($request);
        $form = $this->formFactory->create(SearchType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $dto->username !== null)
            return $this->redirectToRoute('chat_user', ['username' => $dto->username]);

        return $this->render('warning/index.html.twig', [
            'warnings' => $warnings,
            'form' => $form->createView(),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->security->getUser();

        $dto = WarningDto::createFromRequest($request);
        $form = $this->formFactory->create(WarningType::class, $dto, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $error = $this->sendWarningMessages($dto, $user);

            if ($error === null)  {
                $this->addFlash('success', sprintf('Your warning has been sent to %s', $dto->getTargetsString()));
            } else {
                $this->addFlash('error', sprintf('One of the targets (channels / users) couldn\'t be messaged: %s', $error));
            }

            return $this->redirectToRoute('warning_list');
        }

        return $this->render('warning/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function delete(Warning $warning): RedirectResponse
    {
        $this->warningRepository->delete($warning);

        $this->addFlash('success', 'Warning has been removed');

        return $this->redirectToRoute('warning_list');
    }

    private function sendWarningMessages(WarningDto $dto, User $user): ?string
    {
        $error = null;

        $warnings = $dto->createWarnings($user);
        foreach ($warnings as $warning) {
            if ($warning->getUsername() !== null) {
                $user = $this->integration->getUserInfo($warning->getUsername())->getApiResponse()?->getUser();

                if ($user !== null)
                    $warning->setUserName($user->getUsername());

                if ($user === null) {
                    $suggestions = $this->integration->autoCompleteUsername($warning->getUsername())->getApiResponse()->getSuggestions();

                    if (isset($suggestions) && count($suggestions) === 1) {
                        $warning->setUsername($suggestions[0]['username']);
                    }
                }
            }

            $this->warningRepository->save($warning);

            $log = $this->integration->warn($warning, $dto->informUser, $dto->informTeam);

            if ($log->getExtractedError() !== null)
                $error .= $log->getExtractedError() . ' ';
        }

        return $error;
    }
}