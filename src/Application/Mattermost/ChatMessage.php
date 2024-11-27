<?php
declare(strict_types=1);

namespace App\Application\Mattermost;

class ChatMessage {
    private array $message;

    public string $channel_id;

    public function __construct(string $channel, string $text)
    {
        $this->message = [
            'message' => $text,
        ];
        $this->channel_id = $channel;
    }

    public function addAttachment(string $text, bool $isReason, bool $isImage = false): self
    {
        $attachment = [
            [
                'fallback' => $isImage ? '' : $this->prepareTextForSending($text),
                'text' => $isImage ? '' : $this->prepareTextForSending($text),
                'collapsed' => false,
            ]
        ];

        if ($isReason) {
            $attachment[0]['author_name'] = 'Reason:';
        }

        if ($isImage)
            $attachment[0]['image_url'] = $text;


        if (array_key_exists('attachments', $this->message))
            $this->message['props']['attachments'][] = $attachment;
        else
            $this->message['props']['attachments'] = $attachment;


        return $this;
    }

    public function addColor(string $color): self
    {
        $this->message['props']['attachments'][0]['color'] = $color;

        return $this;
    }


    public function getMessage(): string
    {
        $this->message['channel_id'] = $this->channel_id;

        return json_encode($this->message);
    }

    private function prepareTextForSending(string $message, bool $asMarkdown = false): string
    {
        return $message;
        // The content in this if is super strange, but please don't touch in unless you feel like doing some debugging
        if ($asMarkdown) {
            return '```
' . $message . '
```';
        }

        return preg_replace('#\r\n#i', " \\\r\n", $message);
    }
}