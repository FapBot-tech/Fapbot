<?php
declare(strict_types=1);

namespace App\Application\RocketChat\Response;

use App\Application\Chat\Response\MessagesInterface;

class Messages implements MessagesInterface {
    public array $messages = [];

    public function __construct(array $messages)
    {
        try {
            foreach($messages as $message) {
                if (!$message instanceof Message)
                    $message = new Message($message);

                $this->messages[] = $message;
            }
        } catch (\Exception $e) {
            dd($messages);
        }
    }

    public function getFirstMessage(): Message
    {
        return $this->messages[0];
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}