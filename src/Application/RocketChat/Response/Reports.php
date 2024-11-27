<?php
declare(strict_types=1);

namespace App\Application\RocketChat\Response;


final class Reports
{
    public array $reports = [];

    public function __construct(array $reports)
    {
        foreach($reports as $report)
            $this->reports[] = new Report($report);
    }

    public function getFirstReport(): Report
    {
        return $this->reports[0];
    }
}