<?php
declare(strict_types=1);


namespace App\Application\Console;

use App\Application\Chat\IntegrationInterface;
use App\Application\Mattermost\MatterMostIntegration;
use App\Application\RocketChat\Response\Message;
use App\Entity\ApiLog;
use App\Entity\LastExecute;
use App\Entity\Mute;
use App\Entity\Repository\ApiLogRepository;
use App\Entity\Repository\ChannelRepository;
use App\Entity\Repository\LastExecuteRepository;
use App\Entity\Repository\MuteRepository;
use App\Entity\Repository\UserRepository;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


final class ExtractMutesFromChannels extends Command
{
    private UserRepository $userRepository;
    private ChannelRepository $channelRepository;
    private MuteRepository $muteRepository;
    private LastExecuteRepository $lastExecuteRepository;
    private ApiLogRepository $apiLogRepository;
    private IntegrationInterface $integration;

    public function __construct(
        UserRepository $userRepository,
        ChannelRepository $channelRepository,
        MuteRepository $muteRepository,
        LastExecuteRepository $lastExecuteRepository,
        ApiLogRepository $apiLogRepository,
        IntegrationInterface $integration,
        string $name = null
    ) {
        $this->userRepository = $userRepository;
        $this->channelRepository = $channelRepository;
        $this->muteRepository = $muteRepository;
        $this->lastExecuteRepository = $lastExecuteRepository;
        $this->apiLogRepository = $apiLogRepository;
        $this->integration = $integration;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName('mutes:extract')
            ->setDescription('Attempt to load in mute messages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->integration instanceof MatterMostIntegration)
            return Command::SUCCESS;

        $lastRun = $this->lastExecuteRepository->getLastExecuteForIdentifier(LastExecute::IDENTIFIER_EXTRACT_MUTES);
        $lasExecute = new LastExecute(LastExecute::IDENTIFIER_EXTRACT_MUTES);

        $outputArray = [];
        $outputArray[] = 'Last run: ' . ($lastRun?->getLastRunUtc()->format('Y-m-d H:i:s') ?? 'No last run found');
        $outputArray[] = 'Now: '. (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $output->writeln($lastRun?->getLastRunUtc()->format('Y-m-d H:i:s') ?? 'No last run found');

        $channels = [
            '2AYSoHtdDNtuS7pRt' => '3 days',
            'BTXbDwDyMndmENbGx' => '7 days',
        ];

        foreach ($channels as $id => $length) {
            try {
                /** @var Message[] $messages */
                $messages = $this->integration->getGroupMessages($id, $lastRun?->getLastRunUtc());
            } catch (\Exception $e) {
                $output->writeln('Error fetching messages for '. $id);

                return Command::SUCCESS;
            }

            if (count($messages) === 0) {
                $outputArray[] = 'No messages found for '. $id;
                $output->writeln('No messages found for '. $id);
                continue;
            }

            foreach ($messages as $message) {
                if (!isset($message->msg)) {
                    $outputArray[] = 'Message skipped because theres no message';
                    $output->writeln('Message skipped because theres no message');
                    continue;
                }

                if ($message->u->username === 'fapbot') {
                    $outputArray[] = 'Message skipped because its from fapbot';
                    $output->writeln('Message skipped because its from fapbot');
                    continue;
                }

                if (str_contains($message->msg, '!FapBot') || str_contains($message->msg, '!fapbot') || str_contains($message->msg, '!Fapbot') || str_contains($message->msg, '!fapBot')) {
                    $outputArray[] = 'Message skipped because it webhook command';
                    $output->writeln('Message skipped because it webhook command');
                    continue;
                }

                if (str_contains($message->msg, '@') && str_contains($message->msg, '#')) {
                    $startChannelName = strpos($message->msg, '#');
                    $endChannelName = strpos($message->msg, ' ', $startChannelName);
                    $channelName = preg_replace("/[^A-Za-z-]/", '', substr($message->msg, $startChannelName + 1, $endChannelName - $startChannelName));

                    $startUserName = strpos($message->msg, '@');
                    $endUserName = strpos($message->msg, ' ', $startUserName);
                    $userName = preg_replace("/[^A-Za-z-]/", '', substr($message->msg, $startUserName + 1, $endUserName - $startUserName));

                    $startReason = strpos($message->msg, '(');
                    $endReason = strpos($message->msg, ')', $startReason + 1);

                    if ($startReason === false || $endReason === false) {
                        $reason = "No reason provided, logged for automatic unmute";
                    } else {
                        $reason = substr($message->msg, $startReason + 1, $endReason - $startReason - 1);
                    }

                    try {
                        $channel = $this->channelRepository->findByName($channelName);
                    } catch (\Exception $e) {
                        $outputArray[] = 'Error creating mute for ' . $userName . ' in ' . $channelName;
                        $output->writeln('Error creating mute for ' . $userName . ' in ' . $channelName);
                        $this->integration->reactToMessage($message->_id, 'warning');
                        continue;
                    }

                    $user = $this->userRepository->findOneBy(['username' => $message->u->username]) ?? $this->userRepository->findOneBy(['id' => 1]);

                    $mute = new Mute(
                        $user,
                        new ArrayCollection([$channel]),
                        $userName,
                        (string) $user->getId(),
                        $reason,
                        new DateTime('+' . $length, new DateTimeZone('UTC')),
                    );

                    $this->muteRepository->save($mute);

                    $this->integration->reactToMessage($message->_id, 'white_check_mark');

                    $outputArray[] = 'Mute created for ' . $userName . ' in ' . $channelName;
                    $output->writeln('Mute created for ' . $userName . ' in ' . $channelName);
                }
                elseif (str_contains($message->msg, '@') || str_contains($message->msg, '#')) {
                    $this->integration->reactToMessage($message->_id, 'warning');
                }
                else {
                    $outputArray[] = 'Message skipped because it doesnt contain a channel and user';
                    $output->writeln('Message skipped because it doesnt contain a channel and user');
                }
            }
        }

        $apiLog = new ApiLog(true, json_encode((object) $outputArray), null, json_encode((object) ['Mutes extracted from channels']));
        $this->lastExecuteRepository->save($lasExecute);
        $this->apiLogRepository->save($apiLog);

        return Command::SUCCESS;
    }
}