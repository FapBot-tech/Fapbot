<?php
declare(strict_types=1);

namespace App\Application\Chat\Response;

interface MessagesInterface
{
    public function getMessages(): array;
}