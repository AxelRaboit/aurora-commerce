<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Dashboard\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Billing\Dashboard\View\DashboardViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/billing', name: 'billing_dashboard')]
#[IsGranted('billing.invoices.view')]
final class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardViewBuilder $viewBuilder) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Billing/admin/dashboard/index.html.twig', $this->viewBuilder->indexView());
    }
}
