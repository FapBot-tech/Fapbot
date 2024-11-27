<?php
declare(strict_types=1);

namespace App\Application\RocketChat\Response;

use App\Application\Chat\Response\MessagesInterface;
use App\Application\Chat\Response\ResponseInterface;
use App\Application\Chat\Response\UserInterface;
use App\Application\Chat\Response\UsersInterface;
use Exception;

class Response implements ResponseInterface {
    // Required properties
    public bool $success;
    public string $response;

    // Optional properties
    public string $channel;
    public ?User $user = null;
    public ?Users $users = null;
    public ?Message $message = null;
    public ?Messages $messages = null;
    public ?Reports $reports = null;
    public ?Suggestions $suggestions = null;

    public ?int $total = null;
    public ?int $count = null;
    public ?int $offset = null;

    public static function createErrorResponse(): self
    {
        $response = new self('');
        $response->success = false;

        return $response;
    }

    public function __construct(string $json) {
        $this->response = $json;

        try {
            $data = json_decode($json, true);
        } catch (\Exception $e) {
            $this->success = false;

            return;
        }

        if (!is_array($data)) {
            $this->success = false;

            return;
        }

        try {
            $this->success = $data['success'] ?? false;
        } catch (Exception $e) {
            $this->success = false;

            return;
        }

        try {
            if(array_key_exists('channel', $data))
                $this->channel = $data['channel'];

            if(array_key_exists('user', $data))
                $this->user = new User($data['user']);

            if(array_key_exists('users', $data))
                $this->users = new Users($data['users']);

            if(array_key_exists('messages', $data))
                $this->messages = new Messages($data['messages']);

            if(array_key_exists('message', $data))
                $this->message = new Message($data['message']);

            if(array_key_exists('total', $data))
                $this->total = $data['total'];

            if(array_key_exists('count', $data))
                $this->count = $data['count'];

            if(array_key_exists('offset', $data))
                $this->offset = $data['offset'];

            if(array_key_exists('reports', $data))
                $this->reports = new Reports($data['reports']);

            if (array_key_exists('items', $data))
                $this->suggestions = new Suggestions($data['items']);

        } catch (\Exception $e) {
            dd($data, $e);
        }
    }

    public static function createFromUsers(Users $users): self
    {
        $self = new self("");
        $self->success = true;

        $self->users = $users;

        return $self;
    }

    public static function createFromUser(User $user): self
    {
        $self = new self("");
        $self->success = true;

        $self->user = $user;

        return $self;
    }

    public static function createFromMessages(array $messages): self
    {
        $self = new self("");
        $self->success = true;

        $self->messages = new Messages($messages);

        return $self;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getSuggestions(): ?array
    {
        return $this->suggestions?->suggestions;
    }

    public function getMessages(): ?MessagesInterface
    {
        return $this->messages;
    }

    public function getUsers(): ?UsersInterface
    {
        return $this->users;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function getRequest(): ?string
    {
        return null;
    }
}
