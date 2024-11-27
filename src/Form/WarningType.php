<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Repository\ChannelRepository;
use App\Entity\User;
use App\Form\CustomTypes\CombinableTextType;
use App\Form\CustomTypes\PrefilledReasonType;
use App\Form\CustomTypes\SearchableChannelType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class WarningType extends AbstractType {
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
            ->add('channels', SearchableChannelType::class, [
                'required' => false,
                'label' => 'Channel(s)*',
                'choices' => $channels
            ])
            ->add('username', CombinableTextType::class, [
                'label' => 'Username(s)*',
                'required' => false
            ])
            ->add('reason', PrefilledReasonType::class, [
                'label' => 'Message',
                'constraints' => [
                    new Length(null, 0, 10000),
                ]
            ])
            ->add('informUser', CheckboxType::class, [
                'label' => 'Inform user(s) / channel(s)?',
                'required' => false
            ])
        ;

        if ($user->isAdmin()) {
            $builder->add('informTeam', CheckboxType::class, [
                'label' => 'Inform team?',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
       $resolver->setDefaults([
           'dataClass' => WarningDto::class,
           'user' => null
       ]);
    }
}