<?php
declare(strict_types=1);

namespace App\Form;


abstract class AbstractProblematicUserDto
{
    public ?string $username;
    public bool $problematicOverride = false;

    public function __construct(bool $problematicOverride = false)
    {
        $this->problematicOverride = $problematicOverride;
    }
}