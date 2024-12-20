<?php
declare(strict_types=1);


namespace App\Form;

use App\Form\CustomTypes\UsernameType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


final class SearchType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', UsernameType::class, [
                'required' => false,
                'attr' => [
                    'no_label' => true,
                    'placeholder' => 'Search...'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['data_class' => SearchDto::class]);
    }
}