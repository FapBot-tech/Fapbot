<?php
declare(strict_types=1);


namespace App\Form\Validation;

use App\Form\WarningDto;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


final class ValidWarningValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof WarningDto)
            return;

        if ($value->channels->count() === 0 && ($value->username === null || trim($value->username) === ''))
            $this->context
                ->buildViolation("Either a channel or username needs to be selected to warn someone")
                ->addViolation();
    }
}