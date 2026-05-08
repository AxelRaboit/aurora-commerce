<?php

declare(strict_types=1);

namespace Aurora\Core\User\Dto;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'profile.errors.name_required')]
        public string $name,
        #[Assert\NotBlank(message: 'profile.errors.email_invalid')]
        #[Assert\Email(message: 'profile.errors.email_invalid')]
        public string $email,
        #[Assert\AtLeastOneOf(constraints: [
            new Assert\Blank(),
            new Assert\Length(min: 8, minMessage: 'profile.errors.password_too_short'),
        ], message: 'profile.errors.password_too_short', includeInternalMessages: false)]
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
