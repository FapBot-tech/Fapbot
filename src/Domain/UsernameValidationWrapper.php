<?php
declare(strict_types=1);


namespace App\Domain;

use App\Form\Validation\ValidUsername;


final class UsernameValidationWrapper
{
    #[ValidUsername]
    public string $username;

    public function __construct(string $username)
    {
        $this->username = $username;
    }
}