<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Auth\View\Frontend;

use Aurora\Core\Platform\Auth\Security\Frontend\LoginAuthenticator;
use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Configuration\Theme\Service\ThemeContext;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Builds the Twig payloads for the public auth pages (login, register,
 * verify, forgot, reset, account). Centralises the locale + front context
 * + theme context shape so each controller action stays focused on flow.
 */
final readonly class AuthViewBuilder
{
    public function __construct(
        private Context $context,
        private ThemeContext $themeContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function loginView(string $locale, ?string $lastEmail, ?AuthenticationException $error): array
    {
        return [
            ...$this->base($locale),
            'checkPath' => LoginAuthenticator::CHECK_PATH,
            'error' => $error,
            'lastEmail' => $lastEmail,
        ];
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed>  $values
     *
     * @return array<string, mixed>
     */
    public function registerView(string $locale, bool $registrationEnabled, array $errors, array $values, bool $submitted): array
    {
        return [
            ...$this->base($locale),
            'registrationEnabled' => $registrationEnabled,
            'errors' => $errors,
            'values' => $values,
            'submitted' => $submitted,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function registerConfirmView(string $locale, ?string $pendingEmail, bool $resent): array
    {
        return [
            ...$this->base($locale),
            'pendingEmail' => $pendingEmail,
            'resent' => $resent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyEmailView(string $locale, bool $success): array
    {
        return [
            ...$this->base($locale),
            'success' => $success,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forgotPasswordView(string $locale, bool $sent): array
    {
        return [
            ...$this->base($locale),
            'sent' => $sent,
        ];
    }

    /**
     * @param array<string, string> $errors
     *
     * @return array<string, mixed>
     */
    public function resetPasswordView(string $locale, string $selector, string $token, bool $invalid, array $errors): array
    {
        return [
            ...$this->base($locale),
            'invalid' => $invalid,
            'errors' => $errors,
            'selector' => $selector,
            'token' => $token,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function accountView(string $locale, ?UserInterface $user): array
    {
        return [
            ...$this->base($locale),
            'user' => $user,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function base(string $locale): array
    {
        return [
            'locale' => $locale,
            'context' => $this->context,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
        ];
    }
}
