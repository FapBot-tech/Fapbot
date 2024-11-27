<?php
declare(strict_types=1);


namespace App\Form\Validation;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


#[\Attribute]
final class ChannelNotBlankValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof Collection) return;

        if ($value->count() === 0)
            $this->context
                ->buildViolation("At least one channel is required")
                ->addViolation();
    }
}