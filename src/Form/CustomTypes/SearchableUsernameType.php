<?php
declare(strict_types=1);


namespace App\Form\CustomTypes;

use App\Entity\Channel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;


final class SearchableUsernameType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }
}