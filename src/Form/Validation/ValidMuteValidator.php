<?php
declare(strict_types=1);


namespace App\Form\Validation;

use App\Entity\Repository\ChannelRepository;
use App\Entity\User;
use App\Form\MuteDto;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


final class ValidMuteValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof MuteDto)
            return;

        if ($value->channel->count() === 0 || $value->username === null || trim($value->username) === '')
            $this->context
                ->buildViolation("Both a channel and a username are required to mute someone")
                ->addViolation();
    }
}