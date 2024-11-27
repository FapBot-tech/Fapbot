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


class BulkSearchType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('usernames', TextareaType::class, [
                'attr' => [
                    'style' => "min-height: 346px",
                    'placeholder' => 'Enter your list of usernames, each username has to go on a new line and no other text should be entered.
                    
Example:
MyxR
SouthernSharpshooter
DutchBouncer
etc.'
                ],
                'constraints' => [
                    new Length(null, 1, 10000),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
       $resolver->setDefaults([
           'dataClass' => BulkSearchDto::class,
       ]);
    }
}