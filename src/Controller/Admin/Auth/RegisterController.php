<?php

declare(strict_types=1);

namespace App\Controller\Admin\Auth;

use App\Contract\User\UserManagerInterface;
use App\DTO\Auth\RegisterInput;
use App\Entity\User;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Enum\HttpMethodEnum;
use App\Repository\SettingRepository;
use App\Service\PayloadValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly UserManagerInterface $userManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly SettingRepository $settingRepository,
    ) {}

    #[Route('/register', name: 'admin_register')]
    public function register(Request $request): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $registrationEnabled = $this->settingRepository->getBoolean(ApplicationParameterEnum::AdminRegistrationEnabled->value);

        if (!$registrationEnabled || !$request->isMethod(HttpMethodEnum::Post->value)) {
            return $this->render('admin/auth/register.html.twig', [
                'registrationEnabled' => $registrationEnabled,
                'errors' => [],
                'values' => [],
            ]);
        }

        $input = RegisterInput::fromRequest($request);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->render('admin/auth/register.html.twig', [
                'registrationEnabled' => true,
                'errors' => $errors,
                'values' => $request->request->all(),
            ]);
        }

        $this->userManager->register($input->name, $input->email, $input->password);
        $request->getSession()->set('_admin_pending_email', $input->email);

        return $this->redirectToRoute('admin_register_confirm');
    }

    #[Route('/register/confirm', name: 'admin_register_confirm', methods: [HttpMethodEnum::Get->value])]
    public function registerConfirm(Request $request): Response
    {
        $pendingEmail = $request->getSession()->get('_admin_pending_email');
        $resent = $request->query->getBoolean('resent');

        return $this->render('admin/auth/register_confirm.html.twig', [
            'pendingEmail' => $pendingEmail,
            'resent' => $resent,
        ]);
    }

    #[Route('/resend-verification', name: 'admin_resend_verification', methods: [HttpMethodEnum::Post->value])]
    public function resendVerification(Request $request): Response
    {
        $email = $request->getSession()->get('_admin_pending_email', '');
        if ('' !== $email) {
            $this->userManager->resendVerificationEmail($email);
        }

        return $this->redirectToRoute('admin_register_confirm', ['resent' => 1]);
    }

    #[Route('/verify-email/{token}', name: 'admin_verify_email', methods: [HttpMethodEnum::Get->value])]
    public function verifyEmail(string $token): Response
    {
        $user = $this->userManager->verifyEmail($token);

        return $this->render('admin/auth/verify_email.html.twig', [
            'success' => $user instanceof User,
        ]);
    }
}
