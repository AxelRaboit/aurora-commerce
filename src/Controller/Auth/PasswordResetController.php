<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Contract\PasswordResetManagerInterface;
use App\DTO\ResetPasswordInput;
use App\Entity\ResetPasswordRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordResetController extends AbstractController
{
    public function __construct(
        private readonly PasswordResetManagerInterface $passwordResetManager,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgot(Request $request): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $status = null;

        if ($request->isMethod('POST')) {
            $email = mb_trim($request->request->get('email', ''));
            $this->passwordResetManager->sendResetLink($email);
            $status = $this->translator->trans('auth.forgot_password.sent');
        }

        return $this->render('auth/forgot_password.html.twig', ['status' => $status]);
    }

    #[Route('/reset-password/{selector}/{token}', name: 'app_reset_password')]
    public function reset(string $selector, string $token, Request $request): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $resetRequest = $this->passwordResetManager->validateToken($selector, $token);

        if (!$resetRequest instanceof ResetPasswordRequest) {
            $this->addFlash('error', $this->translator->trans('auth.reset_password.invalid_link'));

            return $this->redirectToRoute('app_forgot_password');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $input = ResetPasswordInput::fromRequest($request);

            $violations = $this->validator->validate($input);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $field = $violation->getPropertyPath();
                    $errors[$field] ??= $violation->getMessage();
                }
            } else {
                $this->passwordResetManager->resetPassword($resetRequest, $input->password);
                $this->addFlash('success', $this->translator->trans('auth.reset_password.success'));

                return $this->redirectToRoute('admin_login');
            }
        }

        return $this->render('auth/reset_password.html.twig', [
            'errors' => $errors,
            'selector' => $selector,
            'token' => $token,
        ]);
    }
}
