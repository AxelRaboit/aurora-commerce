<?php

declare(strict_types=1);

namespace App\Core\User\DTO;

use App\Core\Auth\Validator\UniqueEmail;
use App\Core\Locale\Enum\LocaleEnum;
use App\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateUserInput
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
        public LocaleEnum $locale,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: Str::trimFromArray($data, 'name'),
            email: Str::trimFromArray($data, 'email'),
            password: (string) ($data['password'] ?? ''),
            locale: LocaleEnum::tryFrom((string) ($data['locale'] ?? '')) ?? LocaleEnum::default(),
        );
    }
}
