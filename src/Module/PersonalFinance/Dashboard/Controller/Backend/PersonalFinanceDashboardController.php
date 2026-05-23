<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Dashboard\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\PersonalFinance\Dashboard\Service\PersonalFinanceDashboardServiceInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance/dashboard', name: 'backend_personal_finance_dashboard')]
#[IsGranted('personal_finance.dashboard.use')]
final class PersonalFinanceDashboardController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceDashboardServiceInterface $dashboardService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->render('@PersonalFinance/backend/dashboard/index.html.twig', [
            'snapshot' => $this->dashboardService->snapshot($user),
            'refreshPath' => $this->urlGenerator->generate('backend_personal_finance_dashboard_refresh'),
            'walletTransactionsPath' => $this->urlGenerator->generate('backend_personal_finance_transactions', ['walletId' => '__walletId__']),
        ]);
    }

    #[Route('/refresh', name: '_refresh', methods: [HttpMethodEnum::Get->value])]
    public function refresh(): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        return $this->jsonSuccess(['snapshot' => $this->dashboardService->snapshot($user)]);
    }
}
