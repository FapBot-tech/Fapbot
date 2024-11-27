<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Reason;
use Symfony\Component\Validator\Constraints as Assert;


class ReasonDto {
    #[Assert\Length(min: 1, max: 140)]
    public string $name;

    #[Assert\NotBlank]
    public string $reason;

    public static function createFromEntity(Reason $reason): self
    {
        $dto = new self();
        $dto->name = $reason->getName();
        $dto->reason = $reason->getReason();

        return $dto;
    }

    public function toEntity(): Reason
    {
        return new Reason($this->name, $this->reason);
    }
}