<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FrontRegisterInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'frontend.errors.name_required')]
        #[Assert\Length(max: 100)]
        public string $name,
        #[Assert\NotBlank(message: 'frontend.errors.email_required')]
        #[Assert\Email(message: 'frontend.errors.email_invalid')]
        #[Assert\Length(max: 180)]
        public string $email,
        #[Assert\NotBlank(message: 'frontend.errors.password_required')]
        #[Assert\Length(min: 8, minMessage: 'frontend.errors.password_too_short')]
        public string $password,
        public string $locale,
    ) {}

    public static function fromArray(array $data, string $locale = 'fr'): self
    {
        return new self(
            name: Str::trimOrNull((string) ($data['name'] ?? '')) ?? '',
            email: Str::trimOrNull((string) ($data['email'] ?? '')) ?? '',
            password: (string) ($data['password'] ?? ''),
            locale: $locale,
        );
    }
}
