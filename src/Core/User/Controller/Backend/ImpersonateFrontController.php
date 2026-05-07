<?php

declare(strict_types=1);

namespace Aurora\Core\User\Controller\Backend;

use Aurora\Core\Auth\Service\ImpersonationTokenService;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/users/{id}/impersonate-frontend', name: 'backend_users_impersonate_frontend', methods: [HttpMethodEnum::Get->value])]
#[IsGranted('ROLE_ADMIN')]
final class ImpersonateFrontController extends AbstractController
{
    public function __construct(
        private readonly ImpersonationTokenService $tokenService,
        private readonly FrontContext $frontContext,
    ) {}

    public function __invoke(User $user): RedirectResponse
    {
        if (!$user->isFrontUser()) {
            throw $this->createNotFoundException('Not a frontend user.');
        }

        $token = $this->tokenService->generate($user);
        $locale = $this->frontContext->defaultLocale();

        return $this->redirectToRoute('frontend_impersonate_redeem', [
            'locale' => $locale,
            'token' => $token,
        ]);
    }
}
