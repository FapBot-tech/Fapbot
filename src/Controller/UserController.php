<?php
declare(strict_types=1);


namespace App\Controller;

use App\Application\Chat\IntegrationInterface;
use App\Application\RocketChat\ChatMessage;
use App\Application\RocketChat\Connector;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Form\ForgotPasswordDto;
use App\Form\ForgotPasswordType;
use App\Form\NewPasswordDto;
use App\Form\NewPasswordType;
use App\Form\ResetPasswordDto;
use App\Form\ResetPasswordType;
use App\Form\UserDto;
use App\Form\UserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


class UserController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $userRepository;
    private FormFactoryInterface $formFactory;
    private AuthenticationUtils $authenticationUtils;
    private Security $security;
    private IntegrationInterface $integration;
    private Connector $connector;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        FormFactoryInterface $formFactory,
        AuthenticationUtils $authenticationUtils,
        Security $security,
        IntegrationInterface $integration,
        Connector $connector
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->formFactory = $formFactory;
        $this->authenticationUtils = $authenticationUtils;
        $this->security = $security;
        $this->integration = $integration;
        $this->connector = $connector;
    }

    public function login(): Response
    {
        return $this->render('user/login.html.twig', [
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    public function userManagement(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('user/overview.html.twig', [
            'users' => $users
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): Response
    {
        $loggedInUser = $this->security->getUser();

        $dto = new UserDto();
        $form = $this->formFactory->create(UserType::class, $dto, [
            'user' => $loggedInUser,
            'canEditPassword' => true,
            'targetUser' => null
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $roles = $dto->getDatabaseRoles();
            $user = new User($dto->username, $roles);
            $user->setPassword($this->passwordHasher->hashPassword(
                $user,
                $dto->password ?? str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
            ));
            $user->setChannels($dto->channels);

            $this->userRepository->save($user);

            $this->addFlash('success', 'User has been added');

//            if ($dto->sendWelcomeMessage) {
//                $this->chatIntegration->sendHtmlMessage('@' . $dto->username, 'user/welcome_message.html.twig', [
//                    'user' => $user,
//                    'secret' => $this->getSecret($user),
//                ]);
//            }

            return $this->redirectToRoute('user_management');
        }

        return $this->render('/user/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, User $user): Response
    {
        $loggedInUser = $this->security->getUser();

        $dto = UserDto::createFromUser($user);
        $form = $this->formFactory->create(UserType::class, $dto, [
            'isEdit' => true,
            'user' => $loggedInUser,
            'canEditPassword' => $loggedInUser->isHigherRankThan($user),
            'targetUser' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $user->setUsername($dto->username);
            $roles = $dto->getDatabaseRoles();
            $user->setRoles($roles);
            $user->setChannels($dto->channels);

            if (isset($dto->password) && $dto->password !== null)
                $user->setPassword($this->passwordHasher->hashPassword(
                    $user,
                    $dto->password
                ));

            $this->userRepository->save($user);

            $this->addFlash('success', 'User has been updated');

            return $this->redirectToRoute('user_management');
        }

        return $this->render('/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'editPassword' => $loggedInUser->isHigherRankThan($user)
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    public function delete(User $user): Response
    {
        $myxr = $this->userRepository->findOneBy(['id' => 1]);

        $mutes = $user->getMutes();
        foreach ($mutes as $mute) {
            $mute->setUser($myxr);
        }

        $warnings = $user->getWarnings();
        foreach ($warnings as $warning) {
            $warning->setUser($myxr);;
        }

        $this->userRepository->delete($user);

        $this->addFlash('success', 'User has been deleted');

        return $this->redirectToRoute('user_management');
    }

    public function updatePassword(Request $request): Response
    {
        $dto = new NewPasswordDto();
        $form = $this->formFactory->create(NewPasswordType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->security->getUser();
            if ($this->passwordHasher->isPasswordValid($user, $dto->currentPassword)) {
                if ($dto->newPassword == $dto->newPasswordAgain) {
                    $user->setPassword($this->passwordHasher->hashPassword($user, $dto->newPassword));
                    $this->userRepository->save($user);

                    $this->addFlash('success', 'Your password has been updated');

                    return $this->redirectToRoute('index');
                }
            }

            $this->addFlash('error', 'The password you entered as your current on is incorrect, contact @MyxR if you\'re getting stuck');
        }

        return $this->render('/user/changePassword.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function resetPassword(Request $request, User $user, string $secret): Response
    {
        if ($secret !== $this->getSecret($user)) {
            $this->addFlash('error', 'Incorrect password reset code');

            return $this->redirectToRoute('user_login');
        }

        $dto = new ResetPasswordDto();
        $form = $this->formFactory->create(ResetPasswordType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->newPassword));
            $this->userRepository->save($user);

            $this->addFlash('success', 'Your password has been updated');

            return $this->redirectToRoute('user_login');
        }

        return $this->render('/user/resetPassword.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function forgotPassword(Request $request): Response
    {
        $dto = new ForgotPasswordDto();
        $form = $this->formFactory->create(ForgotPasswordType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->userRepository->findByUsername($dto->username);

            if ($user === null) {
                $this->addFlash('error', 'User not found');
                $message = new ChatMessage('@MyxR', sprintf('@%s tried to request a password but they have no account', $dto->username));
                $this->connector->postMessage($message->getMessage());

                return $this->redirectToRoute('user_login');
            }

            $secret = $this->getSecret($user);
            $resetLink = $this->generateUrl('reset_password', [
                'id' => $user->getId(),
                'secret' => $secret
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $this->integration->sendPasswordResetLink($user, $resetLink);

            $this->addFlash('notice', 'Password reset link has been sent to you in a direct message from FapBot');

            return $this->redirectToRoute('user_login');
        }

        return $this->render('/user/forgotPassword.html.twig', [
            'form' => $form->createView()
        ]);
    }

    private function getSecret(User $user): string{
        return str_replace('/', '', substr($user->getPassword(), 16, 8));
    }
}