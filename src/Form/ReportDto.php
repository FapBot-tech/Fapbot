<?php
declare(strict_types=1);

namespace App\Form;

use App\Application\Chat\IntegrationInterface;
use App\Entity\Report;
use App\Form\Validation\ValidUsername;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


final class ReportDto
{
    #[NotBlank]
    #[ValidUsername]
    public string $reporterName;
    #[NotBlank]
    #[ValidUsername]
    public string $reportedName;
    #[NotBlank]
    public string $reasonSelect;
    #[Length(max: 500)]
    public ?string $reason = null;
    public ?string $reportedMessageLink = null;

    public function toEntity(IntegrationInterface $integration): Report
    {
        $reporter = $integration->getUserInfo($this->reporterName)->getApiResponse()?->getUser();
        $reported = $integration->getUserInfo($this->reportedName)->getApiResponse()?->getUser();

        return new Report(
            $reporter->getUsername(),
            $reporter->getId(),
            sprintf('%s: %s', $this->reasonSelect, $this->reason),
            $this->reportedMessageLink,
            $reported->getUsername(),
            $reported->getId(),
        );
    }
}