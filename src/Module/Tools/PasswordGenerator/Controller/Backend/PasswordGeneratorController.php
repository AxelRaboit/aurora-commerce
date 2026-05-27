<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\PasswordGenerator\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/tools/password-generator', name: 'backend_tools_password_generator')]
#[IsGranted('tools.password_generator.use')]
final class PasswordGeneratorController extends AbstractController
{
    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Tools/backend/password-generator/index.html.twig');
    }
}
