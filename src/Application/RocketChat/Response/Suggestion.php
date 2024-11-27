<?php
declare(strict_types=1);

namespace App\Application\RocketChat\Response;


final class Suggestion
{
    public string $_id;
    public string $username;
    public int $score = 1;

    public function __construct(array $suggestion)
    {
        $this->_id = $suggestion['_id'];
        $this->username = $suggestion['username'];

        if (array_key_exists('score', $suggestion))
            $this->score = $suggestion['score'];
    }
}