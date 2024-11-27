<?php
declare(strict_types=1);


namespace App\Entity;

use App\Application\Chat\Response\ResponseInterface;
use App\Application\RocketChat\Response\Response;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Entity\Repository\ApiLogRepository')]
#[ORM\Index(name: 'created_idx', columns: ['created'])]
#[ORM\Index(name: 'success_idx', columns: ['success'])]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'cache.app')]
class ApiLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'boolean')]
    private bool $success;

    #[ORM\Column(type: 'text')]
    private string $response;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $extractedError;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created;

    public ?ResponseInterface $apiResponse = null;

    public function __construct(bool $success, string $response, ?string $extractedError, ?string $message = null)
    {
        $this->success = $success;
        $this->response = $response;
        $this->extractedError = $extractedError;
        $this->message = $message;
        $this->created = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public static function createFromResponse(ResponseInterface $response): self
    {
        $self = new self($response->isSuccess(), "", null, "");

        $self->apiResponse = $response;
        $self->response = $response->getResponse();

        return $self;
    }

    public static function createEmptySuccess(): self
    {
        return new self(true, "", null, "");
    }

    public static function createEmptyError(): self
    {
        return new self(false, "", null, "");
    }

    public static function createFromRocketChatResponse(Response $response, ?string $message = null): self
    {
        return new self($response->success, $response->response, self::extractErrors($response->response), $message);
    }

    public static function extractErrors(string $result): ?string
    {
        if (str_contains($result, '503 Service Temporarily Unavailable'))
            return 'Chat could not be reached';

        $result = json_decode($result);
        if ($result->success)
            return null;

        return match ($result->error) {
            default => $result->error,
            'room_is_blocked' => 'This user has blocked FapBots direct messages. The direct message to the user with the reason was thus not received. (message 2 in the mute guide)',
            "Cannot read property 'starred' of undefined" => 'The message could not be sent, possibly due to being caught by the word filter',
            'User is not in this room [error-user-not-in-room]' => "The user you're trying to mute or unmute is not in this channel",
            '[invalid-channel]' => "The username or channel name you've entered is incorrect",
        };
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function getExtractedError(): ?string
    {
        return $this->extractedError;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function getResponseObject(): ?object
    {
        if (str_contains($this->response, '503 Service Temporarily Unavailable'))
            return (object) ['success' => false, 'error' => 'Chat could not be reached'];

        $object = json_decode($this->response);

        if ($object === null)
            return null;

        if (isset($object->message) && is_object($object->message)) {
            unset($object->message->parseUrls);
            unset($object->message->groupable);
            unset($object->message->avatar);
            unset($object->message->u);
            unset($object->message->rid);
            unset($object->message->_id);
            unset($object->message->_updatedAt);
            unset($object->message->mentions);
            unset($object->message->md);

            if (count($object->message->channels) === 0)
                unset($object->message->channels);
        }

        return $object;
    }

    public function getMessageObject(): ?object
    {
        if ($this->message === null)
            return null;

        return json_decode($this->message);
    }

    public function isErrorMapped(): bool
    {
        if ($this->extractedError === null)
            return true;

        $response = json_decode($this->response);
        $error = self::extractErrors($this->response);

        return $error !== $response?->error;
    }

    public function isBlock(): bool
    {
        $result = json_decode($this->response);
        if ($result->success)
            return false;

        return $result->error === 'room_is_blocked';
    }

    public function getApiResponse(): ?ResponseInterface
    {
        return $this->apiResponse;
    }

    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }
}