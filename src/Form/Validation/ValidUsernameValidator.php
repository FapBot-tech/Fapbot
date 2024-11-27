<?php
declare(strict_types=1);


namespace App\Form\Validation;

use App\Application\Chat\IntegrationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


final class ValidUsernameValidator extends ConstraintValidator
{
    private IntegrationInterface $integration;

    public function __construct(IntegrationInterface $integration)
    {
        $this->integration = $integration;
    }

    public function validate($value, Constraint $constraint): void
    {
        if ($value === null || $value[0] === '@' || is_string($value) === false) {
            $this->context
                ->buildViolation("Username is required, and can't start with a @")
                ->addViolation();
            return;
        }

        try {
            $log = $this->integration->getUserInfo($value);
            $user = $log->getApiResponse()?->getUser();

            if ($user === null)
                $this->context
                    ->buildViolation("User could not be found, could it be a typo?")
                    ->addViolation();
        } catch (\Exception $e) {
            $this->context
                ->buildViolation("User could not be found, could it be a typo?")
                ->addViolation();
        }

    }
}