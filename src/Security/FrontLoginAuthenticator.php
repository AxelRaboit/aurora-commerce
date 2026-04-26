<?php

declare(strict_types=1);

namespace App\Security;

use App\Enum\HttpMethodEnum;
use App\Enum\User\UserStatusEnum;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class FrontLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const CHECK_PATH = '/front-login-check';

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator) {}

    public function supports(Request $request): bool
    {
        return $request->isMethod(HttpMethodEnum::Post->value)
            && self::CHECK_PATH === $request->getPathInfo();
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->getString('email');
        $locale = $request->request->getString('_locale', 'fr');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, function ($userIdentifier) {
                return null; // resolved by the provider
            }),
            new PasswordCredentials($request->request->getString('password')),
            [
                new CsrfTokenBadge('front_authenticate', $request->request->getString('_csrf_token')),
                new RememberMeBadge(),
                new UserBadge($email),
            ],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $user = $token->getUser();

        // Block admin users from using front login
        if (!method_exists($user, 'isFrontUser') || !$user->isFrontUser()) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        // Block unverified users
        if (method_exists($user, 'getStatus') && UserStatusEnum::PendingVerification === $user->getStatus()) {
            throw new CustomUserMessageAuthenticationException('front.errors.email_not_verified');
        }

        // Block disabled users
        if (method_exists($user, 'getStatus') && UserStatusEnum::Disabled === $user->getStatus()) {
            throw new CustomUserMessageAuthenticationException('front.errors.account_disabled');
        }

        $locale = $request->request->getString('_locale', 'fr');

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('front_account', ['locale' => $locale]));
    }

    protected function getLoginUrl(Request $request): string
    {
        $locale = $request->request->getString('_locale', 'fr');

        return $this->urlGenerator->generate('front_login', ['locale' => $locale]);
    }
}
