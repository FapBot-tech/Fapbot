<?php
declare(strict_types=1);

namespace App\Application\Twig;

use App\Entity\Repository\UserRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


final class IsModeratorExtension extends AbstractExtension
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('is_moderator', [$this, 'isModerator']),
        ];
    }

    public function isModerator(string $username): bool
    {
        $user = $this->userRepository->findByUsername($username);

        return $user !== null;
    }
}