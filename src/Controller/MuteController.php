<?php

namespace App\Controller;

use App\Application\Chat\IntegrationInterface;
use App\Application\Mattermost\MatterMostIntegration;
use App\Entity\Mute;
use App\Entity\Repository\MuteRepository;
use App\Form\MuteDto;
use App\Form\MuteType;
use App\Form\SearchDto;
use App\Form\SearchType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;

class MuteController extends AbstractController
{
    private MuteRepository $muteRepository;
    private FormFactoryInterface $formBuilder;
    private Security $security;
    private IntegrationInterface $integration;

    public function __construct(
        MuteRepository $muteRepository,
        FormFactoryInterface $formBuilder,
        Security $security,
        IntegrationInterface $integration
    ) {
        $this->muteRepository = $muteRepository;
        $this->formBuilder = $formBuilder;
        $this->security = $security;
        $this->integration = $integration;
    }

    public function listMutes(Request $request): Response
    {
        $mutes = $this->muteRepository->findAllActive();

        $dto = SearchDto::fromRequest($request);
        $form = $this->formBuilder->create(SearchType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $dto->username !== null)
            return $this->redirectToRoute('chat_user', ['username' => $dto->username]);

        return $this->render('index.html.twig', [
            'mutes' => $mutes,
            'form' => $form->createView(),
        ]);
    }

    public function createMute(Request $request): Response
    {
        $user = $this->security->getUser();

        $dto = MuteDto::createFromRequest($request);
        $form = $this->formBuilder->create(MuteType::class, $dto, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $chatUser = $this->integration->getUserInfo($dto->username)->getApiResponse()?->getUser();
            $mute = $dto->toMute($user, $chatUser);

            $this->removeAlreadyMutedChannels($mute);

            if ($mute->getChannels()->count() === 0) {
                $this->addFlash('warning', 'No channels were left, user not muted');

                return $this->redirectToRoute('index');
            }

            $this->muteRepository->save($mute);

            $log = $this->integration->mute(
                $mute,
                $dto->informTeam,
                $dto->informChatroom && $mute->getChannels()->count() < 10
            );

            if ($log->isSuccess()) {
                $this->addFlash('success', 'User has been muted');
            } else {
                $this->addFlash('danger', 'User has been muted, but there was an issue informing the chatroom');
            }

            return $this->redirectToRoute('index');
        }

        return $this->render('mute/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function unmute(Mute $mute): RedirectResponse
    {
        $mute->setUnmuted();
        $this->muteRepository->save($mute);

        $this->integration->unmute($mute);
        $this->addFlash('success', 'User has been unmuted');

        return $this->redirectToRoute('index');
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function remove(Mute $mute): RedirectResponse
    {
        $this->muteRepository->delete($mute);

        $this->addFlash('success', 'Mute record has been removed');

        return $this->redirectToRoute('index');
    }

    private function removeAlreadyMutedChannels(Mute $mute): Mute
    {
        foreach ($mute->getChannels() as $channel) {
            $channelMutes = $this->muteRepository->getUserMutesInChannel($channel, $mute->getUserName());

            if (count($channelMutes) > 0) {
                $mute->removeChannel($channel);

                $this->addFlash('notice', sprintf(
                    'This user was already muted in %s, the channel was skipped',
                    $channel->getName()
                ));
            }
        }

        return $mute;
    }
}