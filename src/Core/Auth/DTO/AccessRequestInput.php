<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\DTO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AccessRequestInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'profile.errors.email_invalid')]
        #[Assert\Email(message: 'profile.errors.email_invalid')]
        public string $email,
        public ?string $name,
        public ?string $message,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            email: mb_trim($request->request->get('email', '')),
            name: mb_trim($request->request->get('name', '')) ?: null,
            message: mb_trim($request->request->get('message', '')) ?: null,
        );
    }
}
