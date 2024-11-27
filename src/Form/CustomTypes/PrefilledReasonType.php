<?php
declare(strict_types=1);


namespace App\Form\CustomTypes;

use App\Entity\Reason;
use App\Entity\Repository\ReasonRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;


final class PrefilledReasonType extends AbstractType
{
    private ReasonRepository $reasonRepository;

    public function __construct(ReasonRepository $reasonRepository)
    {
        $this->reasonRepository = $reasonRepository;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $reasons = $this->reasonRepository->findAll();

        $json = json_encode(array_map(function (Reason $reason) {
            return [
                'name' => $reason->getName(),
                'reason' => $reason->getReason()
            ];
        }, $reasons));

        $resolver->setDefaults([
            'attr' => [
                'data-reason-list' => $json
            ]
        ]);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}