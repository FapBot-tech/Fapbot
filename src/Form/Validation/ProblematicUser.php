<?php
declare(strict_types=1);

namespace App\Form\Validation;

use Symfony\Component\Validator\Constraint;


#[\Attribute]
final class ProblematicUser extends Constraint
{
    public function validatedBy(): string
    {
        return ProblematicUserValidator::class;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}