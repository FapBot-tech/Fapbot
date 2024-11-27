<?php
declare(strict_types=1);

namespace App\Application\Listeners;

use App\Application\Chat\IntegrationInterface;
use App\Application\RocketChat\Integration;
use App\Entity\LastExecute;
use App\Entity\Mute;
use App\Entity\Repository\LastExecuteRepository;
use App\Entity\Repository\MuteRepository;
use App\Entity\Repository\WarningRepository;
use App\Entity\Warning;
use App\Form\Validation\ProblematicUserValidator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;


#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]

final class OutdatedMuteListListener
{
    private LastExecuteRepository $lastExecuteRepository;
    private MailerInterface $mailer;

    public function __construct(
        LastExecuteRepository $lastExecuteRepository,
        MailerInterface $mailer
    ) {
        $this->lastExecuteRepository = $lastExecuteRepository;
        $this->mailer = $mailer;
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        /** @var LastExecute $lastExecute */
        $lastExecute = $this->lastExecuteRepository->getLastExecuteForIdentifier(LastExecute::IDENTIFIER_MUTE_LOAD);
        $lastEmail = $this->lastExecuteRepository->getLastExecuteForIdentifier(LastExecute::IDENTIFIER_EMAIL_SENT);

        if ($lastExecute === null) {
            return;
        }

        if (
            $lastExecute->getLastRunUtc() < new \DateTimeImmutable('-10 minutes', new \DateTimeZone('UTC'))
            && ($lastEmail?->getLastRunUtc() < new \DateTimeImmutable('-2 hours', new \DateTimeZone('UTC')) || $lastEmail === null)
        ) {
            $execute = new LastExecute(LastExecute::IDENTIFIER_EMAIL_SENT);

            $email = new Email();
            $email
                ->from(new Address('noreply@fapbot.tech', 'Mute list checker'))
                ->to('nils@fapbot.tech')
                ->subject('Mute list isn\'t updating')
                ->text(sprintf('The mute list hasn\'t been updated in over 5 minutes. Last update at: %s', $lastExecute->getLastRunUtc()->setTimezone(new \DateTimeZone('Europe/Amsterdam'))->format('Y-m-d H:i:s')))
            ;

            $this->lastExecuteRepository->save($execute);
            $this->mailer->send($email);
        }
    }
}