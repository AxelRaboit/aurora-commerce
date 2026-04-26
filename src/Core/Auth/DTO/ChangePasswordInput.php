<?php

declare(strict_types=1);

namespace App\Core\Auth\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangePasswordInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'profile.errors.current_password_invalid')]
        public string $currentPassword,
        #[Assert\NotBlank(message: 'profile.errors.password_too_short')]
        #[Assert\Length(min: 8, minMessage: 'profile.errors.password_too_short')]
        public string $password,
        #[Assert\NotBlank(message: 'profile.errors.password_mismatch')]
        #[Assert\EqualTo(propertyPath: 'password', message: 'profile.errors.password_mismatch')]
        public string $passwordConfirmation,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            currentPassword: (string) ($data['current_password'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            passwordConfirmation: (string) ($data['password_confirmation'] ?? ''),
        );
    }
}
