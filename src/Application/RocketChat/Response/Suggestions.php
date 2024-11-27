<?php
declare(strict_types=1);

namespace App\Application\RocketChat\Response;


final class Suggestions
{
    /** @var Suggestion[] */
    public array $suggestions = [];

    public function __construct(array $suggestions)
    {
        try {
            foreach($suggestions as $suggestion) {
                $sugges = new Suggestion($suggestion);

                $this->suggestions[] = $sugges;
            }
        } catch (\Exception $e) {
            dump($suggestions, $e);
        }
    }
}