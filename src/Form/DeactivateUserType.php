<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


final class DeactivateUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['user'];

        $disabled = !$options['isDeactivated'];
        if ($user->isSuperAdmin())
            $disabled = false;

        $builder
            ->add('active', CheckboxType::class, [
                'required' => false,
                'disabled' => $disabled,
            ])
            ->add('reason', TextType::class, [
                'disabled' => $disabled,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
                'label' => 'Reason for deactivation',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'dataClass' => DeactivateUserDto::class,
            'user' => null,
            'isDeactivated' => false
        ]);
    }
}