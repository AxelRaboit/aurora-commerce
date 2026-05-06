<?php

declare(strict_types=1);

namespace Aurora\Core\Dashboard\Controller\Admin;

use Aurora\Core\Dashboard\View\DashboardViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend', name: 'backend_')]
#[IsGranted('core.dashboard.view')]
class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardViewBuilder $viewBuilder) {}

    #[Route('', name: 'dashboard')]
    public function index(): Response
    {
        return $this->render('@Core/admin/dashboard/index.html.twig', $this->viewBuilder->indexView());
    }
}
