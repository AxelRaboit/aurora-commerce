<?php

declare(strict_types=1);

namespace App\Controller\Admin\Auth;

use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Repository\SettingRepository;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    public function __construct(private readonly SettingRepository $settingRepository) {}

    #[Route('/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/auth/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'registrationEnabled' => $this->settingRepository->getBoolean(ApplicationParameterEnum::AdminRegistrationEnabled->value),
        ]);
    }

    #[Route('/logout', name: 'admin_logout')]
    public function logout(): never
    {
        throw new LogicException('This method can be blank — it will be intercepted by the logout key on your firewall.');
    }
}
