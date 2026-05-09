<?php

declare(strict_types=1);

namespace Aurora\Core\Frontend\Controller;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Frontend\Service\Router;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class RootDispatchController extends AbstractController
{
    public function __construct(
        private readonly Router $router,
        private readonly Context $context,
    ) {}

    #[Route('/', name: 'frontend_root', priority: 10)]
    public function root(): RedirectResponse
    {
        $front = $this->router->getDefault();

        return $this->redirectToRoute($front->getHomeRoute(), ['locale' => $this->context->defaultLocale()]);
    }
}
