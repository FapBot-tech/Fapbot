<?php
declare(strict_types=1);

namespace App\Application\Mattermost\Response;

use App\Application\Chat\Response\MessagesInterface;
use App\Application\Chat\Response\ResponseInterface;
use App\Application\Chat\Response\UserInterface;

class Response implements ResponseInterface {
    // Required properties
    public bool $success;
    public string $response;
    public string $link = '';
    public ?string $request = null;

    public ?Messages $messages = null;
    public ?Users $users = null;

    public ?Message $message = null;
    public ?User $user = null;

    public static function createErrorResponse(): self
    {
        $response = new self('');
        $response->success = false;

        return $response;
    }

    public function __construct(string $json, bool $skip = false, string $request = null)
    {
        $this->response = $json;
        $this->request = $request;

        try {
            $data = json_decode($json, true);

            if (is_array($data) === false || (array_key_exists('status_code', $data) && $data['status_code'] !== 200)) {
                $this->success = false;
                return;
            }

            if (is_array($data) && array_key_exists('link', $data)) {
                $this->link = $data['link'];
            }

        } catch (\Exception $e) {
            $self->success = false;
            return;
        }

        if ($skip)
            return;


        if (!is_array($data)) {
            $this->success = false;

            return;
        }

        if (array_key_exists('posts', $data)) {
            $this->messages = new Messages($data['posts'], $data['order']);
        }

        if (array_key_exists('users', $data)) {
            $this->users = new Users($data['users']);
        }

        $this->success = true;
    }

    public static function createMessages(string $json): self
    {
        $self = new self($json);

        if ($self->success === false)
            return $self;

        $data = json_decode($json, true);

        $self->messages = new Messages($data['posts'], $data['order']);

        return $self;
    }

    public static function createUsers(string $json): self
    {
        $self = new self($json);

        if ($self->success === false)
            return $self;

        $data = json_decode($json, true);

        $self->users = new Users($data);

        return $self;
    }

    public static function createMessage(string $json): self
    {
        $self = new self($json);

        if ($self->success === false)
            return $self;

        $data = json_decode($json, true);

        $self->message = new Message($data);

        return $self;
    }

    public static function createUser(array $json)
    {
        $self = new self(json_encode($json));

        if ($self->success === false)
            return $self;

        $self->user = new User($json);

        return $self;
    }


    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getSuggestions(): ?array
    {
        return array_map(function($user) {
            return ['username' => $user->username];
        }, $this->users?->users);
    }

    public function getMessages(): ?MessagesInterface
    {
        return $this->messages;
    }

    public function getUsers(): ?Users
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
        return $this->request;
    }
}
