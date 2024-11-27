<?php
declare(strict_types=1);

namespace App\Application\Mattermost\Response;

use App\Application\Chat\Response\MessagesInterface;


final class Messages implements MessagesInterface
{
    public array $messages = [];

    public function __construct(array $messages, array $order)
    {
        foreach ($order as $key) {
            $this->messages[] = new Message($messages[$key]);
        }
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}