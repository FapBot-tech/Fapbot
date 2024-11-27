<?php
declare(strict_types=1);

namespace App\Controller;

use App\Application\Chat\IntegrationInterface;
use App\Application\Chat\Response\MessageInterface;
use App\Entity\Repository\ChannelRepository;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Form\SearchDto;
use App\Form\SearchType;
use App\Infrastructure\TimeRemainingFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;

final class MessageSearchController extends AbstractController
{
    private ChannelRepository $channelRepository;
    private FormFactoryInterface $formFactory;
    private UserRepository $userRepository;
    private Security $security;
    private IntegrationInterface $integration;

    public function __construct(
        ChannelRepository $channelRepository,
        FormFactoryInterface $formFactory,
        UserRepository $userRepository,
        Security $security,
        IntegrationInterface $integration
    ) {
        $this->channelRepository = $channelRepository;
        $this->formFactory = $formFactory;
        $this->userRepository = $userRepository;
        $this->security = $security;
        $this->integration = $integration;
    }

    public function __invoke(Request $request): Response
    {
        $dto = SearchDto::fromRequest($request);
        $form = $this->formFactory->create(SearchType::class, $dto);
        $form->handleRequest($request);

        if (($form->isSubmitted() === false || $form->isValid() === false) && $request->query->has('username') === false)
            return $this->render('message_search/index.html.twig', [
                'form' => $form->createView(),
            ]);

        $loggedInUser = $this->security->getUser();
        $user = $this->userRepository->findByUsername($dto->username);

        if ($user !== null && $loggedInUser->isHigherRankThan($user) === false) {
            $this->addFlash('error', 'You can only search for users with a lower rank than you.');

            return $this->render('message_search/index.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        $channels = $this->channelRepository->findAllExceptTesting();

        return $this->render('message_search/index.html.twig', [
            'form' => $form->createView(),
            'username' => $dto->username,
            'channels' => $channels,
        ]);
    }

    public function deleteMessage(Request $request, string $username, string $messageId, string $channelId): Response
    {
        /** @var User $loggedInUser */
        $loggedInUser = $this->security->getUser();
        $channel = $this->channelRepository->findByIdentifier($channelId);

        if($loggedInUser->hasAccessToChannel([$channel]) === false) {
            $this->addFlash('error', 'Could not delete');

            return $this->redirectToRoute('chat_message_lookup', [ 'username' => $username ]);
        }

        $response = $this->integration->deleteMessage($messageId, $channelId);

        if ($request->query->has('json'))
            return $this->json($response);

        if ($response->isSuccess())
            $this->addFlash('success', 'Message deleted.');
        else
            $this->addFlash('error', 'Could not delete: ' . $response->getResponse() );

        return $this->redirectToRoute('chat_message_lookup', [ 'username' => $username ]);
    }

    public function getJsonMessagesForUserAndChannel(string $username, string $channelId): Response
    {
        $channel = $this->channelRepository->findByIdentifier($channelId);
        $messages = $this->integration->getChannelMessages($channel)->getApiResponse()->getMessages()->getMessages();
        $userInfo = $this->integration->getUserInfo($username)->getApiResponse()?->getUser();
        $userMessages = [];

        /** @var MessageInterface $message */
        foreach ($messages as $message) {
            $timestamp = $message->getCreated();
            $timeAgo = TimeRemainingFormatter::formatRemainingTime($timestamp, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
            $message->setTimeAgo($timeAgo);

            if ($message->getUserId() === $userInfo->getId()) {
                $userMessages[] = $message;

                if ($message->isImage() && count($message->getFiles()) >= 1) {
                    $message->setPublicUrl($this->integration->getImageLink($message->getFiles()[0])->getApiResponse()?->link);
                }
            }
        }

        return $this->json($userMessages);
    }
}