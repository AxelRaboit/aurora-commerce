<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\UserRoleEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/menus', name: 'admin_menus')]
#[IsGranted(UserRoleEnum::Admin->value)]
class MenusController extends AbstractController
{
    #[Route('', name: '')]
    public function index(): Response
    {
        return $this->render('admin/menus/index.html.twig');
    }
}
