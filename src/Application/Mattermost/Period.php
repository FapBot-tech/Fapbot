<?php

namespace App\Application\Mattermost;

enum Period: string
{
    case MINUTE = 'minute';
    case HOUR = 'hour';
    case DAY = 'day';

    function getTTL(int $multiplier = 1): int
    {
        return $multiplier * match ($this) {
            self::MINUTE => 60,
            self::HOUR => 60 * 60,
            self::DAY => 24 * 60 * 60,
        };
    }
}
