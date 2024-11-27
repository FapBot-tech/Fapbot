<?php
declare(strict_types=1);


namespace App\Form;

use App\Entity\User;
use App\Entity\Warning;
use App\Form\Validation\ProblematicUser;
use App\Form\Validation\ValidUsername;
use App\Form\Validation\ValidUsernames;
use App\Form\Validation\ValidWarning;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;


#[ValidWarning]
#[ProblematicUser]
class WarningDto extends AbstractProblematicUserDto {
    public ?Collection $channels = null;

    #[ValidUsernames]
    public ?string $username = null;

    #[NotBlank]
    public string $reason;
    public bool $informUser = true;
    public bool $informTeam = true;

    public static function createFromRequest(Request $request): self
    {
        $self = new self();

        if ($request->query->has('username'))
            $self->username = $request->query->get('username');

        if ($request->query->has('problematicOverride'))
            $self->problematicOverride = $request->query->get('problematicOverride') === 'true';
        else
            $self->problematicOverride = false;

        return $self;
    }

    public function getTargetsString(bool $withAt = true): string
    {
        $targets = '';

        if ($withAt) {
            if ($this->username !== null) {
                $usernames = explode(' ', trim($this->username));
                foreach ($usernames as $username) {
                    $targets .= " @" . $username;
                }
            }
        } else {
            $targets = $this->username ?? '';
        }

        foreach ($this->channels as $channel) {
            $targets .= ' #'. $channel->getName();
        }

        return trim($targets);
    }

    /** @return Warning[] */
    public function createWarnings(User $user): array
    {
        $warnings = [];

        if ($this->username !== null) {
            $usernames = explode(' ', trim($this->username));
            foreach ($usernames as $username) {
                $warnings[] = new Warning($user, $username, null, $this->reason);
            }
        }

        foreach ($this->channels as $channel) {
            $warnings[] = new Warning($user, null, $channel->getIdentifier(), $this->reason);
        }

        return $warnings;
    }
}