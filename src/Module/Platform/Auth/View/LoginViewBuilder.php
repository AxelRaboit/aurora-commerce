<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Auth\View;

use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Builds the Twig payload for the admin login page. Centralises the
 * authentication context (last username / error) plus the registration /
 * access-request feature flags so the controller stays focused on flow.
 */
final readonly class LoginViewBuilder
{
    public function __construct(private SettingRepository $settingRepository) {}

    /**
     * @return array<string, mixed>
     */
    public function loginView(?string $lastUsername, ?AuthenticationException $error): array
    {
        return [
            'last_username' => $lastUsername,
            'error' => $error,
            'registrationEnabled' => $this->settingRepository->getBoolean(ApplicationParameterEnum::AdminRegistrationEnabled->value),
            'accessRequestEnabled' => $this->settingRepository->getBoolean(ApplicationParameterEnum::AdminAccessRequestEnabled->value, true),
        ];
    }
}
