<?php
declare(strict_types=1);

namespace App\Application\RocketChat;

class ChatMessage {
    private array $message;

    public function __construct(string $channel, string $text)
    {
        $this->message = [
            'channel' => $channel,
            'text' => $text,
            'alias' => 'FapBot',
//            'avatar' => 'https:/fapbot.tech/robot.png',
        ];
    }

    public function addAttachement(string $text, bool $isReason, bool $isImage = false): self
    {
        $attachment = [
            [
                'text' => $isImage ? '' : $this->prepareTextForSending($text),
                'collapsed' => false,
            ]
        ];

        if ($isReason)
            $attachment[0]['author_name'] = 'Reason:';

        if ($isImage)
            $attachment[0]['image_url'] = $text;


        if (array_key_exists('attachments', $this->message))
            $this->message['attachments'][] = $attachment;
        else
            $this->message['attachments'] = $attachment;


        return $this;
    }

    public function addColor(string $color): self
    {
        $this->message['attachments'][0]['color'] = $color;

        return $this;
    }


    public function getMessage(): string
    {
        return json_encode($this->message);
    }

    private function prepareTextForSending(string $message, bool $asMarkdown = false): string
    {
        // The content in this if is super strange, but please don't touch in unless you feel like doing some debugging
        if ($asMarkdown) {
            return '```
' . $message . '
```';
        }

        return preg_replace('#\r\n#i', " \\\r\n", $message);
    }
}