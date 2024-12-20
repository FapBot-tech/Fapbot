<?php
declare(strict_types=1);


namespace App\Form;

use App\Form\CustomTypes\UsernameType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ForgotPasswordType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', UsernameType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'dataClass' => ForgotPasswordDto::class
        ]);
    }
}