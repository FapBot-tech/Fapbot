<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Repository\ChannelRepository;
use App\Entity\User;
use App\Form\CustomTypes\PrefilledReasonType;
use App\Form\CustomTypes\SearchableChannelType;
use App\Form\CustomTypes\SearchableUsernameType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;


class MuteType extends AbstractType {
    private ChannelRepository $channelRepository;

    public function __construct(ChannelRepository $channelRepository)
    {
        $this->channelRepository = $channelRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['user'];
        $channels = $this->channelRepository->findForUser($user);

        $builder
            ->add('channel', SearchableChannelType::class, [
                'choices' => $channels,
                'label' => 'Channel(s)'
            ])
            ->add('username', SearchableUsernameType::class)
            ->add('reason', PrefilledReasonType::class, [
                'constraints' => [
                    new Length(null, 1, 600),
                ]
            ])
            ->add('informChatroom', CheckboxType::class, [
                'label' => 'Inform channel(s)?',
                'required' => false
            ])
        ;

        if ($user->isAdmin())
            $builder
                ->add('duration', IntegerType::class, [
                    'attr' => [
                        'min' => 1
                    ],
                    'required' => true,
                ])
                ->add('informTeam', CheckboxType::class, [
                    'label' => 'Inform team?',
                    'required' => false,
                ]);

        else
            $builder->add('duration', ChoiceType::class, [
                'choices' => [
                    '7 days' => 7,
                    '3 days' => 3,
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
       $resolver->setDefaults([
           'dataClass' => MuteDto::class,
           'user' => null
       ]);
    }
}