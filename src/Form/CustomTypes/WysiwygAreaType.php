<?php
declare(strict_types=1);


namespace App\Form\CustomTypes;

use App\Entity\Reason;
use App\Entity\Repository\ReasonRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;


final class WysiwygAreaType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'wysiwyg-area'
            ],
        ]);
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }
}