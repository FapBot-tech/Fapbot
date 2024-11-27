<?php
declare(strict_types=1);


namespace App\Infrastructure;


final class TimeRemainingFormatter
{
    public static function formatRemainingDays(\DateTimeInterface $start, \DateTimeInterface $end): string
    {
        $start = (new \DateTimeImmutable($start->format('Y-m-d H:i:s'), new \DateTimeZone('UTC')));

        $dateDiff = $start->diff($end);
        $hours = (string) (strlen((string)$dateDiff->h) == 2 ? $dateDiff->h : '0'. $dateDiff->h);
        $minutes = (string) (strlen((string)$dateDiff->i) == 2 ? $dateDiff->i + 1 : '0'. $dateDiff->i + 1);

        if (strlen($minutes) === 3)
            $minutes = substr($minutes, 1, 2);

        if ($dateDiff->m > 0)
            return sprintf('%d months, %d days', $dateDiff->m, $dateDiff->d);

        if ($dateDiff->d > 0)
            return sprintf('%d days', $dateDiff->d);

        if ($dateDiff->h > 0)
            return sprintf('%s hour(s) %s min', $hours, $minutes);

        return sprintf('%s min', $dateDiff->i + 1);
    }

    public static function formatRemainingTime(\DateTimeInterface $start, \DateTimeInterface $end): string
    {
        if ($start->getTimezone()->getName() !== 'UTC')
            $start = (new \DateTimeImmutable($start->format('Y-m-d H:i:s'), new \DateTimeZone('UTC')));

        if ($end->getTimezone()->getName() !== 'UTC')
            $end = (new \DateTimeImmutable($end->format('Y-m-d H:i:s'), new \DateTimeZone('UTC')));

        $dateDiff = $start->diff($end);
        $hours = (string) (strlen((string)$dateDiff->h) == 2 ? $dateDiff->h : '0'. $dateDiff->h);
        $minutes = (string) (strlen((string)$dateDiff->i) == 2 ? $dateDiff->i + 1 : '0'. $dateDiff->i + 1);

        if (strlen($minutes) === 3)
            $minutes = substr($minutes, 1, 2);

        if ($dateDiff->y > 0)
            return sprintf('%d years, %d months', $dateDiff->y, $dateDiff->m);

        if ($dateDiff->m > 0)
            return sprintf('%d months, %d days', $dateDiff->m, $dateDiff->d);

        if ($dateDiff->d > 0)
            return sprintf('%d days, %s:%s', $dateDiff->d, $hours, $minutes);

        if ($dateDiff->h > 0)
            return sprintf('%s hour(s) %s min', $dateDiff->h, $dateDiff->i + 1);

        return sprintf('%s min', $dateDiff->i + 1);
    }
}