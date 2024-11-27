<?php
declare(strict_types=1);

namespace App\Application\Chat\Response;


interface UserInterface
{
    public function getId(): string;
    public function isActive(): bool;
    public function isUsernameCorrected(): bool;
    public function setCorrectedUsername(bool $corrected): void;
    public function getStatus(): string;
    public function getEmail(): string;

    public function getUsername(): string;
    public function setStatus(string $status): void;
}