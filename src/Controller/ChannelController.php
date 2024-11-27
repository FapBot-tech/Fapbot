<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Entity\Repository\ChannelRepository;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Form\ChannelDto;
use App\Form\ChannelType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;

class ChannelController extends AbstractController
{
    private ChannelRepository $channelRepository;
    private FormFactoryInterface $formFactory;
    private Security $security;
    private UserRepository $userRepository;

    public function __construct(
        ChannelRepository $channelRepository,
        FormFactoryInterface $formFactory,
        Security $security,
        UserRepository $userRepository
    ) {
        $this->channelRepository = $channelRepository;
        $this->formFactory = $formFactory;
        $this->security = $security;
        $this->userRepository = $userRepository;
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function listChannels(): Response
    {
        $channels = $this->channelRepository->findAll();

        return $this->render('/channel/index.html.twig', [
            'channels' => $channels
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function create(Request $request): Response
    {
        $dto = new ChannelDto();
        $form = $this->formFactory->create(ChannelType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $channel = new Channel($dto->name, $dto->identifier);
            $this->channelRepository->save($channel);

            /** @var User $user */
            $user = $this->security->getUser();
            $this->userRepository->save($user);

            $this->addFlash('success', 'Channel has been added');

            return $this->redirectToRoute('channel_list');
        }

        return $this->render('channel/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function edit(Request $request, Channel $channel): Response
    {
        $dto = ChannelDto::fromChannel($channel);
        $form = $this->formFactory->create(ChannelType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $channel = $dto->updateChannel($channel);
            $this->channelRepository->save($channel);

            $this->addFlash('success', 'Channel has been updated');

            return $this->redirectToRoute('channel_list');
        }

        return $this->render('channel/edit.html.twig', [
            'form' => $form->createView(),
            'channel' => $channel
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, Channel $channel): Response
    {
        $this->channelRepository->delete($channel);
        $this->addFlash('info', 'Channel has been removed');

        return $this->redirectToRoute('channel_list');
    }
}