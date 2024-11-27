<?php
declare(strict_types=1);


namespace App\Controller;

use App\Application\Chat\IntegrationInterface;
use App\Application\Mattermost\MatterMostIntegration;
use App\Entity\BlockedUser;
use App\Entity\Repository\BlockedUserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;


final class BlockedUserController extends AbstractController
{
    private BlockedUserRepository $blockedUserRepository;
    private IntegrationInterface $integration;

    public function __construct(
        BlockedUserRepository $blockedUserRepository,
        IntegrationInterface $integration
    ) {
        $this->blockedUserRepository = $blockedUserRepository;
        $this->integration = $integration;
    }

    public function index(): Response
    {
        if ($this->integration instanceof MatterMostIntegration)
            return $this->render('feature_not_supported.twig');

        $blockedUsers = $this->blockedUserRepository->findAllBlocked();

        return $this->render('chat_user/blocked_users.html.twig', [
            'blockedUsers' => $blockedUsers
        ]);
    }

    public function checkUser(BlockedUser $user): RedirectResponse
    {
        if ($this->integration instanceof MatterMostIntegration)
            return $this->redirectToRoute('blocked_user');

        $log = $this->integration->checkForBlocked($user->getUsername());

        if ($log->isBlock()) {
            $this->addFlash('info', 'This user still has FapBot blocked');

            $user->setUpdated();
        } else {
            $this->addFlash('success', 'FapBot has been unblocked and the user removed from the naughty list');

            $user->unblock();
        }

        $this->blockedUserRepository->save($user);

        return $this->redirectToRoute('blocked_user');
    }
}