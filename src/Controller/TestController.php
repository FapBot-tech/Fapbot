<?php
declare(strict_types=1);


namespace App\Controller;

use App\Application\Chat\IntegrationInterface;
use App\Application\RocketChat\Connector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;
use \App\Application\Mattermost\Connector as MattermostConnector;

final class TestController extends AbstractController
{
    private Security $security;
    private Connector $connector;
    private MattermostConnector $mattermostConnector;
    private FormFactoryInterface $formFactory;
    private IntegrationInterface $integration;
    private string $redisUrl;

    public function __construct(
        Security $security,
        Connector $connector,
        MattermostConnector $mattermostConnector,
        FormFactoryInterface $formFactory,
        IntegrationInterface $integration,
        string $redisUrl
    ) {
        $this->security = $security;
        $this->connector = $connector;
        $this->mattermostConnector = $mattermostConnector;
        $this->formFactory = $formFactory;
        $this->integration = $integration;
        $this->redisUrl = $redisUrl;
    }

    public function __invoke(Request $request): Response
    {
        $users = $this->integration->getChannelUsers('atscdn45ibdt7cocrjkww96myw');

        return $this->render('test/index.html.twig', [
            'users' => $users
        ]);
    }
}