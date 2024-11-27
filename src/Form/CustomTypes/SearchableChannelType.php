<?php
declare(strict_types=1);


namespace App\Form\CustomTypes;

use App\Entity\Channel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;


final class SearchableChannelType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'class' => Channel::class,
            'multiple' => true,
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}