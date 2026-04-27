<?php

declare(strict_types=1);

namespace Aurora\Core\Dashboard\Controller\Dev;

use Aurora\Core\Dashboard\Service\AdminStatsService;
use Aurora\Core\User\Enum\UserRoleEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/dashboard', name: 'dev_dashboard')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class OverviewController extends AbstractController
{
    public function __construct(private readonly AdminStatsService $statsService) {}

    #[Route('', name: '')]
    public function __invoke(): Response
    {
        return $this->render('@Core/admin/administration/index.html.twig', [
            'tab' => 'overview',
            'stats' => $this->statsService->getStats(),
        ]);
    }
}
