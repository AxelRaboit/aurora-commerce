<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Controller\Admin;

use Aurora\Core\Auth\View\LoginViewBuilder;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    public function __construct(private readonly LoginViewBuilder $viewBuilder) {}

    #[Route('/backend/login', name: 'backend_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('backend_dashboard');
        }

        return $this->render('@Core/admin/auth/login.html.twig', $this->viewBuilder->loginView(
            $authenticationUtils->getLastUsername(),
            $authenticationUtils->getLastAuthenticationError(),
        ));
    }

    #[Route('/backend/logout', name: 'backend_logout')]
    public function logout(): never
    {
        throw new LogicException('This method can be blank — it will be intercepted by the logout key on your firewall.');
    }
}
