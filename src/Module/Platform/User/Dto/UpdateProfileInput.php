<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\User\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Platform\Auth\Validator\UniqueEmail;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProfileInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'profile.errors.name_required')]
        public string $name,
        #[Assert\NotBlank(message: 'profile.errors.email_invalid')]
        #[Assert\Email(message: 'profile.errors.email_invalid')]
        #[UniqueEmail(excludeSelf: true, message: 'profile.errors.email_taken')]
        public string $email,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: Str::trimFromArray($data, 'name'),
            email: Str::trimFromArray($data, 'email'),
        );
    }
}
