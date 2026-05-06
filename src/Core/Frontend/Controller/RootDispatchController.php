<?php

declare(strict_types=1);

namespace Aurora\Core\Frontend\Controller;

use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Frontend\Service\FrontRouter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class RootDispatchController extends AbstractController
{
    public function __construct(
        private readonly FrontRouter $frontRouter,
        private readonly FrontContext $frontContext,
    ) {}

    #[Route('/', name: 'frontend_root', priority: 10)]
    public function root(): RedirectResponse
    {
        $front = $this->frontRouter->getDefault();

        return $this->redirectToRoute($front->getHomeRoute(), ['locale' => $this->frontContext->defaultLocale()]);
    }
}
