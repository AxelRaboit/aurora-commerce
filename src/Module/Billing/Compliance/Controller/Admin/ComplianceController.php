<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Compliance\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\Billing\Compliance\View\ComplianceViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/billing/compliance', name: 'backend_billing_compliance')]
#[IsGranted('billing.invoices.view')]
final class ComplianceController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(private readonly ComplianceViewBuilder $viewBuilder) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Billing/admin/compliance/index.html.twig');
    }

    #[Route('/report', name: '_report', methods: [HttpMethodEnum::Get->value])]
    public function report(): JsonResponse
    {
        return $this->jsonSuccess($this->viewBuilder->buildReport());
    }
}
