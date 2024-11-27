<?php
declare(strict_types=1);


namespace App\Form\Validation;

use App\Application\Chat\IntegrationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


final class ValidUsernamesValidator extends ConstraintValidator
{
    private IntegrationInterface $integration;

    public function __construct(IntegrationInterface $chatIntegration)
    {
        $this->integration = $chatIntegration;
    }

    public function validate($value, Constraint $constraint): void
    {
        if ($value === null)
            return;

        $usernames = explode(' ', trim($value));

        foreach ($usernames as $username) {
            if ($username[0] === '@')
                $this->context
                    ->buildViolation("Usernames can't start with a @")
                    ->addViolation();

            $log = $this->integration->getUserInfo($username);
            $user = $log->getApiResponse()?->getUser();
            if ($user === null)
                $this->context
                    ->buildViolation(sprintf("User could not be found: %s, could it be a typo?", $username))
                    ->addViolation();
        }
    }
}