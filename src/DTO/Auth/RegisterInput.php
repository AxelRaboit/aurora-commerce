<?php

declare(strict_types=1);

namespace App\DTO\Auth;

use App\Validator\Constraint\UniqueEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'profile.errors.name_required')]
        public string $name,
        #[Assert\NotBlank(message: 'profile.errors.email_invalid')]
        #[Assert\Email(message: 'profile.errors.email_invalid')]
        #[UniqueEmail(message: 'profile.errors.email_taken')]
        public string $email,
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
            name: mb_trim($request->request->get('name', '')),
            email: mb_trim($request->request->get('email', '')),
            password: $request->request->get('password', ''),
            passwordConfirmation: $request->request->get('password_confirmation', ''),
        );
    }
}
