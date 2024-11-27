<?php
declare(strict_types=1);

namespace App\Application\Listeners;

use App\Application\Chat\IntegrationInterface;
use App\Application\RocketChat\Integration;
use App\Entity\Mute;
use App\Entity\Repository\MuteRepository;
use App\Entity\Repository\WarningRepository;
use App\Entity\Warning;
use App\Form\Validation\ProblematicUserValidator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;


#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]

final class ProblematicUserListener
{
    private MuteRepository $muteRepository;
    private WarningRepository $warningRepository;
    private IntegrationInterface $integration;

    public function __construct(
        MuteRepository $muteRepository,
        WarningRepository $warningRepository,
        IntegrationInterface $integration
    ) {
        $this->muteRepository = $muteRepository;
        $this->warningRepository = $warningRepository;
        $this->integration = $integration;
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Mute && !$entity instanceof Warning)
            return;

        $username = $entity->getUserName() ?? $entity->getChannelId();

        if (str_contains($username, '#'))
            return;

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $lastWeek = $now->modify('-1 week');
        $lastMonth = $now->modify('-1 month');

        $weeklyWarningCount = $this->warningRepository->countInPeriodForUsername($lastWeek, $now, $username);
        $monthlyWarningCount = $this->warningRepository->countInPeriodForUsername($lastMonth, $now, $username);
        $weeklyMuteCount = $this->muteRepository->countInPeriodForUsername($lastWeek, $now, $username);
        $monthlyMuteCount = $this->muteRepository->countInPeriodForUsername($lastMonth, $now, $username);

        if ($weeklyWarningCount >= 3 || $monthlyWarningCount >= 5 || $weeklyMuteCount >= 3 || $monthlyMuteCount >= 5 || in_array($username, ProblematicUserValidator::PREVIOUS_DEACTIVATIONS)) {
            $this->integration->problematicUser(
                $username,
                $weeklyMuteCount,
                $monthlyMuteCount,
                $weeklyWarningCount,
                $monthlyWarningCount,
            );
        }
    }
}