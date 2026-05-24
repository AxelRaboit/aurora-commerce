<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Statistics\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Module\PersonalFinance\Statistics\Service\PersonalFinanceStatisticsServiceInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/personal-finance/statistics', name: 'backend_personal_finance_statistics')]
#[IsGranted('personal_finance.statistics.use')]
final class PersonalFinanceStatisticsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly PersonalFinanceStatisticsServiceInterface $statisticsService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $months = $this->resolvePeriod($request->query->getInt('months'));

        return $this->render('@PersonalFinance/backend/statistics/index.html.twig', [
            'snapshot' => $this->statisticsService->snapshot($user, $months),
            'refreshPath' => $this->urlGenerator->generate('backend_personal_finance_statistics_refresh'),
        ]);
    }

    #[Route('/refresh', name: '_refresh', methods: [HttpMethodEnum::Get->value])]
    public function refresh(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();
        $months = $this->resolvePeriod($request->query->getInt('months'));

        return $this->jsonSuccess(['snapshot' => $this->statisticsService->snapshot($user, $months)]);
    }

    private function resolvePeriod(int $param): int
    {
        return in_array($param, [3, 6, 12], true) ? $param : 6;
    }
}
