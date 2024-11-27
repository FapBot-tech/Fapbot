<?php
declare(strict_types=1);


namespace App\Form;

use App\Form\Validation\ValidUsername;
use Symfony\Component\HttpFoundation\Request;


class BulkSearchDto
{
    public string $usernames = '';

    public function __construct(string $usernames = '')
    {
        $this->usernames = $usernames;
    }

    public function getUsernamesList(): ?array
    {
        $usernames = preg_split("/\r\n|\n|\r/", $this->usernames ?? '');

        return array_map(function ($username) {
            return trim($username);
        }, $usernames);
    }
}