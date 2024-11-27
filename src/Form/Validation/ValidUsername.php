<?php
declare(strict_types=1);


namespace App\Form\Validation;

use Symfony\Component\Validator\Constraint;


#[\Attribute]
final class ValidUsername extends Constraint
{
    public function validatedBy(): string
    {
        return ValidUsernameValidator::class;
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}