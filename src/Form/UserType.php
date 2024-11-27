<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Repository\ChannelRepository;
use App\Entity\User;
use App\Form\CustomTypes\SearchableChannelType;
use App\Form\CustomTypes\UsernameType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class UserType extends AbstractType {
    private ChannelRepository $channelRepository;

    public function __construct(ChannelRepository $channelRepository)
    {
        $this->channelRepository = $channelRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['user'];
        /** @var User $targetUser */
        $targetUser = $options['targetUser'];
        $channels = $this->channelRepository->findForUser($user);

        $choices = match ($user->getRole()) {
            'ROLE_SUPER_ADMIN' => ['Moderator' => 'user', 'Chat Moderator' => 'chat_mod', 'Chat Admin' => 'chat_admin', 'FapBot Admin' => 'admin', 'Super Admin' => 'super_admin'],
            'ROLE_ADMIN' => ['Moderator' => 'user', 'Chat Moderator' => 'chat_mod', 'Chat Admin' => 'chat_admin', 'FapBot Admin' => 'admin'],
            'ROLE_CHAT_ADMIN' => ['Moderator' => 'user', 'Chat Moderator' => 'chat_mod', 'Chat Admin' => 'chat_admin'],
            'ROLE_CHAT_MOD' => ['Moderator' => 'user', 'Chat Moderator' => 'chat_mod'],
            'ROLE_USER' => ['Moderator' => 'user'],
        };

        if ($options['canEditPassword'])
            $builder->add('password', PasswordType::class, [ 'required' => false ]);

        if ($targetUser === null || !$targetUser->hasAccessToAllChannels())
            $builder->add('channels', SearchableChannelType::class, [
                'choices' => $channels,
                'label' => 'Channel(s)'
            ]);

        if (false && ($user->isSuperAdmin() || $options['isEdit'] === false)) {
            $builder->add('sendWelcomeMessage', CheckboxType::class, [
                'label' => 'Send welcome message',
                'required' => false
            ]);
        }

        $builder
            ->add('username', UsernameType::class)
            ->add('role', ChoiceType::class, [
                'choices' => $choices,
                'disabled' => !$options['canEditPassword']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'dataClass' => UserDto::class,
            'isEdit' => false,
            'user' => null,
            'targetUser' => null,
            'canEditPassword' => false
        ]);
    }
}