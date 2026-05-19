<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\User\Dto;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Support\Str;
use Aurora\Module\Platform\Auth\Validator\UniqueEmail;
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
