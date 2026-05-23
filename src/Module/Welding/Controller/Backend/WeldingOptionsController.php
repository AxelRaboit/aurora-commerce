<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Aurora\Module\Hr\Employee\Repository\EmployeeRepository;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use Aurora\Module\PdfForm\PdfTemplate\Repository\PdfTemplateRepository;
use Doctrine\Common\Collections\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Lightweight option endpoints for the Welding pickers (start workflow modal,
 * template editor PDF attachments). Each endpoint is gated by a Welding
 * permission so the welding user doesn't need to inherit hr/pdfform read
 * permissions to populate a dropdown.
 */
#[Route('/backend/welding/options', name: 'backend_welding_options')]
#[IsGranted('welding.workflows.view')]
final class WeldingOptionsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly EmployeeRepository $employeeRepository,
        private readonly PdfTemplateRepository $pdfTemplateRepository,
    ) {}

    #[Route('/employees', name: '_employees', methods: [HttpMethodEnum::Get->value])]
    public function employees(): JsonResponse
    {
        $employees = $this->employeeRepository->createQueryBuilder('e')
            ->orderBy('e.lastName', Order::Ascending->value)
            ->addOrderBy('e.firstName', Order::Ascending->value)
            ->getQuery()
            ->getResult();

        $items = array_map(
            static fn (EmployeeInterface $e): array => [
                'value' => (string) $e->getId(),
                'label' => trim($e->getFirstName().' '.$e->getLastName()),
            ],
            $employees,
        );

        return $this->jsonSuccess(['items' => $items]);
    }

    #[Route('/pdf-templates', name: '_pdf_templates', methods: [HttpMethodEnum::Get->value])]
    public function pdfTemplates(): JsonResponse
    {
        $templates = $this->pdfTemplateRepository->createQueryBuilder('t')
            ->orderBy('t.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();

        $items = array_map(
            static fn (PdfTemplateInterface $t): array => [
                'value' => (string) $t->getId(),
                'label' => $t->getName(),
            ],
            $templates,
        );

        return $this->jsonSuccess(['items' => $items]);
    }
}
