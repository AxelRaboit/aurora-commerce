<?php

declare(strict_types=1);

namespace App\Core\Auth\DTO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ResetPasswordInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'profile.errors.password_too_short')]
        #[Assert\Length(min: 8, minMessage: 'profile.errors.password_too_short')]
        public string $password,
        #[Assert\NotBlank(message: 'profile.errors.password_mismatch')]
        #[Assert\EqualTo(propertyPath: 'password', message: 'profile.errors.password_mismatch')]
        public string $passwordConfirmation,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            password: $request->request->get('password', ''),
            passwordConfirmation: $request->request->get('password_confirmation', ''),
        );
    }
}
