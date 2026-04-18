<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Enum\UserRoleEnum;
use App\Service\AdminStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev', name: 'dev_dashboard')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class OverviewController extends AbstractController
{
    public function __construct(private readonly AdminStatsService $statsService) {}

    #[Route('', name: '')]
    public function __invoke(): Response
    {
        return $this->render('admin/administration/index.html.twig', [
            'tab' => 'overview',
            'stats' => $this->statsService->getStats(),
        ]);
    }
}
