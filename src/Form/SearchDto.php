<?php
declare(strict_types=1);


namespace App\Form;

use App\Form\Validation\ValidUsername;
use Symfony\Component\HttpFoundation\Request;


class SearchDto
{
    #[ValidUsername]
    public ?string $username;

    public function __construct(string $username = null)
    {
        $this->username = $username;
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->query->get('username'));
    }
}