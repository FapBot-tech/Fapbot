<?php
declare(strict_types=1);


namespace App\Form\CustomTypes;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


final class CombinableTextType extends AbstractType
{
    public function getParent(): string
    {
        return HiddenType::class;
    }
}