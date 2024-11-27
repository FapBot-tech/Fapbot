<?php
declare(strict_types=1);


namespace App\Form\Validation;

use Symfony\Component\Validator\Constraint;


#[\Attribute]
final class ValidMute extends Constraint
{
    public function validatedBy(): string
    {
        return ValidMuteValidator::class;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}