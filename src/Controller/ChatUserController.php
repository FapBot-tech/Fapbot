<?php
declare(strict_types=1);


namespace App\Controller;

use App\Application\Chat\IntegrationInterface;
use App\Application\RocketChat\Response\User;
use App\Domain\UsernameValidationWrapper;
use App\Entity\Mute;
use App\Entity\Repository\ChannelRepository;
use App\Entity\Repository\MuteRepository;
use App\Entity\Repository\WarningRepository;
use App\Entity\Warning;
use App\Form\BulkSearchDto;
use App\Form\BulkSearchType;
use App\Form\DeactivateUserDto;
use App\Form\DeactivateUserType;
use App\Form\SearchDto;
use App\Form\SearchType;
use App\Form\Validation\ProblematicUserValidator;
use Doctrine\Common\Collections\Collection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;


final class ChatUserController extends AbstractController
{
    private MuteRepository $muteRepository;
    private WarningRepository $warningRepository;
    private FormFactoryInterface $formFactory;
    private ValidatorInterface $validator;
    private IntegrationInterface $integration;
    private Security $security;
    private ChannelRepository $channelRepository;

    public function __construct(
        MuteRepository $muteRepository,
        WarningRepository $warningRepository,
        FormFactoryInterface $formFactory,
        ValidatorInterface $validator,
        IntegrationInterface $integration,
        Security $security,
        ChannelRepository $channelRepository
    ) {
        $this->muteRepository = $muteRepository;
        $this->warningRepository = $warningRepository;
        $this->formFactory = $formFactory;
        $this->validator = $validator;
        $this->integration = $integration;
        $this->security = $security;
        $this->channelRepository = $channelRepository;
    }

    public function index(Request $request): Response
    {
        $dto = SearchDto::fromRequest($request);
        $form = $this->formFactory->create(SearchType::class, $dto);
        $form->handleRequest($request);

        if (($form->isSubmitted() && $form->isValid()) && $dto->username !== null) {
            return $this->redirectToRoute('chat_user', ['username' => $dto->username]);
        }

        return $this->render('chat_user/index.html.twig', [
            'form' => $form->createView(),
            'mutes' => null,
            'warnings' => null,
            'didSearch' => false,
            'username' => $dto->username,
            'deactivateForm' => null,
            'muteCount' => null,
            'warningCount' => null,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    public function user(Request $request, string $username): Response
    {
        $dto = new SearchDto($username);
        $form = $this->formFactory->create(SearchType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('chat_user', ['username' => $dto->username]);
        }

        $mutes = $this->muteRepository->findUsernameHistory($dto->username);
        $warnings = $this->warningRepository->findUsernameHistory($dto->username);

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $periodStart = $now->modify('-3 months');

        $muteCount = $this->muteRepository->countInPeriodForUsername($periodStart, $now, $dto->username);
        $warningCount = $this->warningRepository->countInPeriodForUsername($periodStart, $now, $dto->username);

        $user = $this->integration->getUserInfo($username)->getApiResponse()?->getUser();
        $loggedInUser = $this->security->getUser();
        $deactivateForm = null;
        if ($user) {
            $deactivateDto = DeactivateUserDto::createFromUser($user);
            $deactivateForm = $this->formFactory->create(DeactivateUserType::class, $deactivateDto, [
                'user' => $this->security->getUser(),
                'isDeactivated' => $user->isActive()
            ]);
            $deactivateForm->handleRequest($request);

            if ($deactivateForm->isSubmitted() && $deactivateForm->isValid()) {
                if ($user->isActive() === false && $loggedInUser->isSuperAdmin() === false) {
                    $this->addFlash('error', 'Only super admins are allowed to re-activate users');
                } else {
                    $apiLog = match (!$user->isActive()) {
                        true => $this->integration->activateUser($username, $loggedInUser->getUsername()),
                        false => $this->integration->deactivateUser($username, $deactivateDto->reason, $loggedInUser->getUsername())
                    };

                    match ($apiLog->isSuccess()) {
                        true => $this->addFlash('success', 'User updated'),
                        false => $this->addFlash('error', 'Something went wrong')
                    };

                    $user = $this->integration->getUserInfo($username)->getApiResponse()?->getUser();
                }
            }
        }

        $channels = $this->channelRepository->findForUser($loggedInUser);

        if ($channels instanceof Collection)
            $channels = $channels->toArray();

        $previouslyDeactivated = in_array($username ?? '', ProblematicUserValidator::PREVIOUS_DEACTIVATIONS);

        return $this->render('chat_user/index.html.twig', [
            'form' => $form->createView(),
            'mutes' => $mutes,
            'warnings' => $warnings,
            'didSearch' => true,
            'username' => $dto->username,
            'muteCount' => $muteCount,
            'warningCount' => $warningCount,
            'deactivateForm' => $deactivateForm?->createView(),
            'user' => $user,
            'channels' => $channels,
            'previouslyDeactivated' => $previouslyDeactivated
        ]);
    }

    #[IsGranted('ROLE_USER')]
    public function asJson(string $username): Response
    {
        $dto = new UsernameValidationWrapper($username);

        $validator = $this->validator->validate($dto);
        if ($validator->count() > 0) {
            return new JsonResponse([
                'error' => $validator->get(0)->getMessage()
            ]);
        }

        $mutes = $this->muteRepository->findUsernameHistory($username);
        $warnings = $this->warningRepository->findUsernameHistory($username);

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $periodStart = $now->modify('-3 months');

        $muteCount = $this->muteRepository->countInPeriodForUsername($periodStart, $now, $dto->username);
        $warningCount = $this->warningRepository->countInPeriodForUsername($periodStart, $now, $dto->username);

        $mutesJson = array_map(function (Mute $mute) {
            return [
                'username' => $mute->getUserName(),
                'mutedBy' => $mute->getUser()->getUsername(),
                'reason' => $mute->getReason(),
                'timeAgo' => $mute->getTimeAgo(),
                'duration' => $mute->getDuration()
            ];
        }, $mutes);

        $warningsJson = array_map(function (Warning $warning) {
            return [
                'username' => $warning->getUserName(),
                'warnedBy' => $warning->getUser()->getUsername(),
                'reason' => $warning->getReason(),
                'timeAgo' => $warning->getTimeAgo()
            ];
        }, $warnings);

        return new JsonResponse([
            'mutes' => $mutesJson,
            'warnings' => $warningsJson,
            'mute_count' => $muteCount,
            'warning_count' => $warningCount,
            'previous_deactivation' => in_array($username, ProblematicUserValidator::PREVIOUS_DEACTIVATIONS)
        ]);
    }

    #[IsGranted('ROLE_USER')]
    public function onlineUsers(): Response
    {
        $onlineUsers = $this->integration->getUserList()->getApiResponse()->getUsers();

        return new JsonResponse($onlineUsers);
    }

    public function bulkSearch(Request $request): Response
    {
        $dto = new BulkSearchDto();
        $form = $this->formFactory->create(BulkSearchType::class, $dto);
        $form->handleRequest($request);

        $results = [];
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($dto->getUsernamesList() as $username) {
                $user = $this->integration->getUserInfo($username)->getApiResponse()?->getUser();
                $correctedUsername = null;

                if ($user === null) {
                    $suggestions = $this->integration->autoCompleteUsername($username)->getApiResponse()->getSuggestions();
                    if (isset($suggestions) && count($suggestions) === 1) {
                        $correctedUsername = $suggestions[0]['username'];
                        $user = $this->integration->getUserInfo($correctedUsername)->getApiResponse()?->getUser();
                    }
                }

                $mutes = $this->muteRepository->findUsernameHistory($correctedUsername ?? $username);
                $warnings = $this->warningRepository->findUsernameHistory($correctedUsername ?? $username);

                $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
                $periodStart = $now->modify('-3 months');

                $muteCount = $this->muteRepository->countInPeriodForUsername($periodStart, $now, $correctedUsername ?? $username);
                $warningCount = $this->warningRepository->countInPeriodForUsername($periodStart, $now, $correctedUsername ?? $username);

                $results[$username] = [
                    'mutes' => $mutes,
                    'muteCount' => $muteCount,
                    'warnings' => $warnings,
                    'warningCount' => $warningCount,
                    'user' => $user,
                    'correctedUsername' => $correctedUsername
                ];
            }
        }

        return $this->render('chat_user/bulk_search.html.twig', [
            'form' => $form->createView(),
            'results' => $results,
            'dto' => $dto
        ]);
    }

    public function autoCompleteUsername(string $partialUsername): JsonResponse
    {
        $apiLog = $this->integration->autoCompleteUsername($partialUsername);
        $suggestions = $apiLog->getApiResponse()->getSuggestions();

        return new JsonResponse($suggestions);
    }
}