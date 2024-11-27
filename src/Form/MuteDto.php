<?php
declare(strict_types=1);

namespace App\Form;

use App\Application\Chat\Response\UserInterface;
use App\Entity\Mute;
use App\Entity\User;
use App\Form\Validation\ProblematicUser;
use App\Form\Validation\ValidMute;
use App\Form\Validation\ValidUsername;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;


#[ValidMute]
#[ProblematicUser]
class MuteDto extends AbstractProblematicUserDto {
    public Collection $channel;

    #[ValidUsername]
    public ?string $username = null;

    #[NotBlank]
    public string $reason;

    #[NotBlank]
    public int $duration;

    public bool $informChatroom = true;
    public bool $informTeam = true;

    public static function createFromRequest(Request $request): self
    {
        $self = new self();

        if ($request->query->get('username'))
            $self->username = $request->query->get('username');

        if ($request->query->has('problematicOverride'))
            $self->problematicOverride = $request->query->get('problematicOverride') === 'true';
        else
            $self->problematicOverride = false;

        return $self;
    }

    public function toMute(User $user, UserInterface $chatUser): Mute
    {
        return new Mute($user, $this->channel, $this->username, $chatUser->getId(), $this->reason, new \DateTime(sprintf('+%d days', $this->duration), new \DateTimeZone('UTC')));
    }
}