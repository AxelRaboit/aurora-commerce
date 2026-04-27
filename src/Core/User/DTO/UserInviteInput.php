<?php

declare(strict_types=1);

namespace Aurora\Core\User\DTO;

use Aurora\Core\Auth\Validator\UniqueEmail;
use Aurora\Core\Support\Str;
use Aurora\Core\User\Enum\UserRoleEnum;
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

        return new self(
            name: Str::trimFromArray($data, 'name'),
            email: Str::trimFromArray($data, 'email'),
            role: Str::trimFromArray($data, 'role'),
            message: Str::trimOrNullFromArray($data, 'message'),
        );
    }
}
