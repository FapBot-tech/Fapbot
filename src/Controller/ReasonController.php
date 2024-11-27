<?php
declare(strict_types=1);


namespace App\Controller;

use App\Entity\Reason;
use App\Entity\Repository\ReasonRepository;
use App\Form\ReasonDto;
use App\Form\ReasonType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


final class ReasonController extends AbstractController
{
    private ReasonRepository $reasonRepository;
    private FormFactoryInterface $formFactory;

    public function __construct(ReasonRepository $reasonRepository, FormFactoryInterface $formFactory)
    {
        $this->reasonRepository = $reasonRepository;
        $this->formFactory = $formFactory;
    }

    public function index(): Response
    {
        $reasons = $this->reasonRepository->findAll();

        return $this->render('reason/index.html.twig', [
            'reasons' => $reasons
        ]);
    }

    public function create(Request $request): Response
    {
        $dto = new ReasonDto();
        $form = $this->formFactory->create(ReasonType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reason = $dto->toEntity();
            $this->reasonRepository->save($reason);

            $this->addFlash('success', 'Reason has been created');


            return $this->redirectToRoute('reason');
        }

        return $this->render('reason/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function update(Request $request, Reason $reason): Response
    {
        $dto = ReasonDto::createFromEntity($reason);
        $form = $this->formFactory->create(ReasonType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reason->update($dto->name, $dto->reason);
            $this->reasonRepository->save($reason);

            $this->addFlash('success', 'Reason has been updated');

            return $this->redirectToRoute('reason');
        }

        return $this->render('reason/edit.html.twig', [
            'form' => $form->createView(),
            'reason' => $reason
        ]);
    }

    public function delete(Reason $reason): Response
    {
        $this->reasonRepository->delete($reason);

        $this->addFlash('success', 'Reason has been deleted');

        return $this->redirectToRoute('reason');
    }
}