<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Announcement;
use App\Entity\Repository\ChannelRepository;
use App\Entity\User;
use App\Form\CustomTypes\SearchableChannelType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class AnnouncementType extends AbstractType {
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
                'choices' => $channels,
                'label' => 'Channel(s)',
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('header', TextType::class, [
                'required' => false
            ])
            ->add('text', TextareaType::class, [
                'attr' => [
                    'style' => "min-height: 346px"
                ],
                'constraints' => [
                    new Length(null, 1, 10000),
                ]
            ])
            ->add('links', TextareaType::class, [
                'required' => false
            ])
            ->add('interval', ChoiceType::class,[
                'label' => 'Interval',
                'choices' => Announcement::INTERVALS,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
       $resolver->setDefaults([
           'dataClass' => AnnouncementDto::class,
           'user' => null
       ]);
    }
}