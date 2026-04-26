<?php

declare(strict_types=1);

namespace App\DTO\User;

use App\Enum\User\UserRoleEnum;
use App\Validator\Constraint\UniqueEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserInviteInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'admin.users.errors.name_required')]
        #[Assert\Length(max: 100, maxMessage: 'admin.users.errors.name_too_long')]
        public string $name,
        #[Assert\NotBlank(message: 'admin.users.errors.email_required')]
        #[Assert\Email(message: 'admin.users.errors.email_invalid')]
        #[Assert\Length(max: 180, maxMessage: 'admin.users.errors.email_too_long')]
        #[UniqueEmail(message: 'admin.users.errors.email_taken')]
        public string $email,
        #[Assert\NotBlank(message: 'admin.users.errors.role_required')]
        #[Assert\Choice(callback: [UserRoleEnum::class, 'allAssignableValues'], message: 'admin.users.errors.role_invalid')]
        public string $role,
        public ?string $message = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = json_decode($request->getContent(), true);
        $data = is_array($data) ? $data : [];

        $rawMessage = mb_trim((string) ($data['message'] ?? ''));

        return new self(
            name: mb_trim((string) ($data['name'] ?? '')),
            email: mb_trim((string) ($data['email'] ?? '')),
            role: mb_trim((string) ($data['role'] ?? '')),
            message: '' === $rawMessage ? null : $rawMessage,
        );
    }
}
