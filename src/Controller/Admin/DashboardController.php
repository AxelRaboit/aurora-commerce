<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\UserRoleEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted(UserRoleEnum::Admin->value)]
class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard/index.html.twig');
    }
}
