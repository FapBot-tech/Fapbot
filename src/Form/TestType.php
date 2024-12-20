<?php
declare(strict_types=1);


namespace App\Form;

use App\Entity\User;
use App\Form\CustomTypes\PrefilledReasonType;
use App\Form\CustomTypes\SearchableChannelType;
use App\Form\CustomTypes\SearchableUsernameType;
use App\Form\CustomTypes\UsernameType;
use App\Form\CustomTypes\WysiwygAreaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


final class TestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', WysiwygAreaType::class, [])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'dataClass' => TestDto::class,
            'user' => null
        ]);
    }
}