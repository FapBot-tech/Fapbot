<?php
declare(strict_types=1);

namespace App\Controller;

use App\Application\Mattermost\Connector;
use App\Entity\LastExecute;
use App\Entity\Mute;
use App\Entity\Repository\LastExecuteRepository;
use App\Entity\Repository\MuteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


final class PluginController extends AbstractController {
    private MuteRepository $muteRepository;
    private Connector $connector;
    private LastExecuteRepository $lastExecuteRepository;

    public function __construct(
        MuteRepository $muteRepository,
        Connector $connector,
        LastExecuteRepository $lastExecuteRepository
    ) {
        $this->muteRepository = $muteRepository;
        $this->connector = $connector;
        $this->lastExecuteRepository = $lastExecuteRepository;
    }

    public function __invoke(): Response
    {
        $lastExecute = new LastExecute(LastExecute::IDENTIFIER_MUTE_LOAD);

        try {
            $mutes = $this->muteRepository->findAllActive();

            $output = [];
            /** @var Mute $mute */
            foreach ($mutes as $mute) {
                foreach ($mute->getChannels() as $channel) {
                    $output[] = [$mute->getUserId(), $channel->getIdentifier()];
                }
            }

            $this->lastExecuteRepository->save($lastExecute);

            return $this->json($output);
        } catch (\Exception $e) {
            return $this->json([]);
        }
    }

    public function channelUsers(string $id): JsonResponse
    {
        $page = 1;
        $onlineUsers = [];

        do {
            $users = $this->connector->getChannelUsers($id, $page)->getUsers()->users;

            for ($i = count($users) - 1; $i > 0; $i-= 10) {
                $user = $users[$i];
                $status = $this->connector->getUserStatus($user->getId());
                $user->status = $status;
            }

            $lastUser = $users[count($users) - 1];
            $lastUserOnline = $this->connector->getUserStatus($lastUser->getId()) !== 'offline';

            $onlineUsers = array_merge($onlineUsers, $users);

        } while (count($users) === 200 && $lastUserOnline);

        for ($i = count($onlineUsers) - 1; $i > 0; $i--) {
            $user = $onlineUsers[$i];
            $status = $this->connector->getUserStatus($user->getId());
            $user->status = $status;

            if ($status === 'offline') {
                array_pop($onlineUsers);
            }
        }


        return $this->json(array_map(function ($user) {
            return $user->getUsername();
        }, $onlineUsers));
    }
}