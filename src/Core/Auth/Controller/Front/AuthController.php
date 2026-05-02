<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Controller\Front;

use Aurora\Core\Auth\DTO\FrontRegisterInput;
use Aurora\Core\Auth\Entity\ResetPasswordRequest;
use Aurora\Core\Auth\View\AuthFrontViewBuilder;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\Theme\Service\ThemeResolver;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Enum\UserTypeEnum;
use Aurora\Core\User\Manager\FrontUserManager;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Core\Validation\Service\PayloadValidator;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly FrontUserManager $frontUserManager,
        private readonly UserRepository $userRepository,
        private readonly SettingRepository $settingRepository,
        private readonly TranslatorInterface $translator,
        private readonly PayloadValidator $payloadValidator,
        private readonly FrontContext $frontContext,
        private readonly ThemeResolver $themeResolver,
        private readonly AuthFrontViewBuilder $viewBuilder,
    ) {}

    #[Route('/{locale}/login', name: 'front_login', requirements: ['locale' => '[a-z]{2}'], priority: 8)]
    public function login(string $locale, Request $request, AuthenticationUtils $authUtils): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        // Already authenticated front user → redirect to account
        if ($this->getUser() instanceof UserInterface && method_exists($this->getUser(), 'isFrontUser') && $this->getUser()->isFrontUser()) {
            return $this->redirectToRoute('front_account', ['locale' => $locale]);
        }

        return $this->render($this->themeResolver->resolve('front_login'), $this->viewBuilder->loginView(
            $locale,
            $authUtils->getLastUsername(),
            $authUtils->getLastAuthenticationError(),
        ));
    }

    #[Route('/front-login-check', name: 'front_login_check', methods: [HttpMethodEnum::Post->value])]
    public function loginCheck(): never
    {
        throw new LogicException('Handled by FrontLoginAuthenticator.');
    }

    #[Route('/{locale}/register', name: 'front_register', requirements: ['locale' => '[a-z]{2}'], priority: 8)]
    public function register(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('front_home', ['locale' => $locale]);
        }

        $registrationEnabled = $this->settingRepository->getBoolean(ApplicationParameterEnum::FrontRegistrationEnabled->value);

        if (!$registrationEnabled || !$request->isMethod(HttpMethodEnum::Post->value)) {
            return $this->render($this->themeResolver->resolve('front_register'), $this->viewBuilder->registerView(
                $locale,
                $registrationEnabled,
                [],
                [],
                false,
            ));
        }

        $input = FrontRegisterInput::fromArray($request->request->all(), $locale);
        $errors = $this->payloadValidator->errors($input);

        if ([] === $errors && $this->userRepository->findOneBy(['email' => $input->email, 'type' => UserTypeEnum::FrontUser])) {
            $errors['email'] = 'front.errors.email_taken';
        }

        if ([] === $errors) {
            $this->frontUserManager->register($input);
            $request->getSession()->set('_front_pending_email', $input->email);

            return $this->redirectToRoute('front_register_confirm', ['locale' => $locale]);
        }

        return $this->render($this->themeResolver->resolve('front_register'), $this->viewBuilder->registerView(
            $locale,
            true,
            $errors,
            [
                'name' => $request->request->get('name', ''),
                'email' => $request->request->get('email', ''),
            ],
            true,
        ));
    }

    #[Route('/{locale}/register/confirm', name: 'front_register_confirm', requirements: ['locale' => '[a-z]{2}'], priority: 8)]
    public function registerConfirm(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($locale);

        $pendingEmail = $request->getSession()->get('_front_pending_email');
        $resent = $request->query->getBoolean('resent');

        return $this->render($this->themeResolver->resolve('front_register_confirm'), $this->viewBuilder->registerConfirmView(
            $locale,
            is_string($pendingEmail) ? $pendingEmail : null,
            $resent,
        ));
    }

    #[Route('/{locale}/resend-verification', name: 'front_resend_verification', requirements: ['locale' => '[a-z]{2}'], methods: [HttpMethodEnum::Post->value], priority: 8)]
    public function resendVerification(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($locale);

        $email = $request->getSession()->get('_front_pending_email', '');
        if ('' !== $email) {
            $this->frontUserManager->resendVerificationEmail($email, $locale);
        }

        return $this->redirectToRoute('front_register_confirm', ['locale' => $locale, 'resent' => 1]);
    }

    #[Route('/{locale}/verify-email/{token}', name: 'front_verify_email', requirements: ['locale' => '[a-z]{2}'], priority: 8)]
    public function verifyEmail(string $locale, string $token): Response
    {
        $this->assertActiveLocale($locale);

        $user = $this->frontUserManager->verifyEmail($token);

        return $this->render($this->themeResolver->resolve('front_verify_email'), $this->viewBuilder->verifyEmailView(
            $locale,
            $user instanceof User,
        ));
    }

    #[Route('/{locale}/forgot-password', name: 'front_forgot_password', requirements: ['locale' => '[a-z]{2}'], priority: 8)]
    public function forgotPassword(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('front_account', ['locale' => $locale]);
        }

        $sent = false;

        if ($request->isMethod(HttpMethodEnum::Post->value)) {
            $email = mb_trim($request->request->getString('email'));
            $this->frontUserManager->sendPasswordResetEmail($email, $locale);
            $sent = true;
        }

        return $this->render($this->themeResolver->resolve('front_forgot_password'), $this->viewBuilder->forgotPasswordView($locale, $sent));
    }

    #[Route('/{locale}/reset-password/{selector}/{token}', name: 'front_reset_password', requirements: ['locale' => '[a-z]{2}'], priority: 8)]
    public function resetPassword(string $locale, string $selector, string $token, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('front_account', ['locale' => $locale]);
        }

        $resetRequest = $this->frontUserManager->validateResetToken($selector, $token);

        if (!$resetRequest instanceof ResetPasswordRequest) {
            return $this->render($this->themeResolver->resolve('front_reset_password'), $this->viewBuilder->resetPasswordView(
                $locale,
                $selector,
                $token,
                true,
                [],
            ));
        }

        $errors = [];

        if ($request->isMethod(HttpMethodEnum::Post->value)) {
            $password = $request->request->getString('password');
            $confirm = $request->request->getString('password_confirmation');

            if ('' === $password || mb_strlen($password) < 8) {
                $errors['password'] = $this->translator->trans('front.errors.password_too_short');
            } elseif ($password !== $confirm) {
                $errors['password_confirmation'] = $this->translator->trans('front.errors.passwords_mismatch');
            }

            if ([] === $errors) {
                $this->frontUserManager->resetPassword($resetRequest, $password);

                return $this->redirectToRoute('front_login', ['locale' => $locale, 'reset' => 1]);
            }
        }

        return $this->render($this->themeResolver->resolve('front_reset_password'), $this->viewBuilder->resetPasswordView(
            $locale,
            $selector,
            $token,
            false,
            $errors,
        ));
    }

    #[Route('/{locale}/account', name: 'front_account', requirements: ['locale' => '[a-z]{2}'], priority: 8)]
    #[IsGranted(UserRoleEnum::User->value)]
    public function account(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($locale);
        $request->setLocale($locale);

        return $this->render($this->themeResolver->resolve('front_account'), $this->viewBuilder->accountView($locale, $this->getUser()));
    }

    #[Route('/{locale}/account/logout', name: 'front_logout', requirements: ['locale' => '[a-z]{2}'], methods: [HttpMethodEnum::Post->value], priority: 8)]
    public function logout(string $locale, Security $security): Response
    {
        $security->logout(validateCsrfToken: false);

        return $this->redirectToRoute('front_home', ['locale' => $locale]);
    }

    private function assertActiveLocale(string $locale): void
    {
        if (!$this->frontContext->isLocaleActive($locale)) {
            throw $this->createNotFoundException(sprintf('LocaleEnum "%s" is not active.', $locale));
        }
    }
}
