<?php
declare(strict_types=1);


namespace App\Form\Validation;

use Symfony\Component\Validator\Constraint;


#[\Attribute]
final class ValidUsernames extends Constraint
{
    public function validatedBy(): string
    {
        return ValidUsernamesValidator::class;
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}