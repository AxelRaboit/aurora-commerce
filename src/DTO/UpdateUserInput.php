<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\LocaleEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'profile.errors.name_required')]
        public string $name,
        #[Assert\NotBlank(message: 'profile.errors.email_invalid')]
        #[Assert\Email(message: 'profile.errors.email_invalid')]
        public string $email,
        public string $password,
        public LocaleEnum $locale,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: mb_trim((string) ($data['name'] ?? '')),
            email: mb_trim((string) ($data['email'] ?? '')),
            password: (string) ($data['password'] ?? ''),
            locale: LocaleEnum::tryFrom((string) ($data['locale'] ?? '')) ?? LocaleEnum::default(),
        );
    }
}
