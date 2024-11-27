<?php
declare(strict_types=1);


namespace App\Controller;

use App\Entity\Repository\ChannelRepository;
use App\Entity\Repository\MuteRepository;
use App\Entity\Repository\WarningRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;


class StatsController extends AbstractController {
    private MuteRepository $muteRepository;
    private ChannelRepository $channelRepository;
    private WarningRepository $warningRepository;

    public function __construct(
        MuteRepository $muteRepository,
        ChannelRepository $channelRepository,
        WarningRepository $warningRepository,
    ) {
        $this->muteRepository = $muteRepository;
        $this->channelRepository = $channelRepository;
        $this->warningRepository = $warningRepository;
    }

    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(Request $request) {
        $end = new \DateTimeImmutable($request->query->get('date') ?? 'now', new \DateTimeZone('UTC'));
        $end = $end->setTime(23, 59, 59);

        $day = $end->modify('-1 day');
        $week = $end->modify('-1 week');
        $month = $end->modify('-1 month');

        $mutesLastDay = $this->muteRepository->countInPeriod($day, $end);
        $mutesLastWeek = $this->muteRepository->countInPeriod($week, $end);
        $mutesLastMonth = $this->muteRepository->countInPeriod($month, $end);

        $warningsLastDay = $this->warningRepository->countInPeriod($day, $end);
        $warningsLastWeek = $this->warningRepository->countInPeriod($week, $end);
        $warningsLastMonth = $this->warningRepository->countInPeriod($month, $end);

        $channelWarnings = $this->warningRepository->findMostPopularChannels();
        $userWarnings = $this->warningRepository->findMostPopularUsers();
        $userMutes = $this->muteRepository->findMostPopularUsers();

        $channels = $this->channelRepository->findAll();
        $channelMutes = [];
        foreach ($channels as $channel) {
            $channelMutes[$channel->getName()] = $this->muteRepository->countMutesInChannel($channel);
        }

        uasort($channelMutes, function ($arg1, $arg2) {
            return $arg1 > $arg2 ? -1 : 1;
        });

        return $this->render('stats/index.html.twig', [
            'mutesLastDay' => $mutesLastDay,
            'mutesLastWeek' => $mutesLastWeek,
            'mutesLastMonth' => $mutesLastMonth,
            'warningsLastDay' => $warningsLastDay,
            'warningsLastWeek' => $warningsLastWeek,
            'warningsLastMonth' => $warningsLastMonth,
            'channelWarnings' => $channelWarnings,
            'muteUsers' => $userMutes,
            'muteChannels' => array_slice($channelMutes, 0, 9),
            'userWarnings' => $userWarnings
        ]);
    }
}