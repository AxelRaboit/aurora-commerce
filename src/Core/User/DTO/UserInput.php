<?php

declare(strict_types=1);

namespace Aurora\Core\User\DTO;

use Aurora\Core\Support\Str;
use Aurora\Core\User\Enum\UserRoleEnum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'admin.users.errors.name_required')]
        #[Assert\Length(max: 100, maxMessage: 'admin.users.errors.name_too_long')]
        public string $name,
        #[Assert\NotBlank(message: 'admin.users.errors.email_required')]
        #[Assert\Email(message: 'admin.users.errors.email_invalid')]
        #[Assert\Length(max: 180, maxMessage: 'admin.users.errors.email_too_long')]
        public string $email,
        #[Assert\NotBlank(message: 'admin.users.errors.role_required')]
        #[Assert\Choice(callback: [UserRoleEnum::class, 'allAssignableValues'], message: 'admin.users.errors.role_invalid')]
        public string $role,
        public string $locale = 'fr',
        #[Assert\Length(min: 8, minMessage: 'admin.users.errors.password_too_short')]
        public ?string $password = null,
        public ?int $managerId = null,
        public ?int $agencyId = null,
        public ?int $serviceId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = json_decode($request->getContent(), true);
        $data = is_array($data) ? $data : [];

        $password = Str::trimOrNullFromArray($data, 'password');
        $managerId = $data['managerId'] ?? null;
        $agencyId = $data['agencyId'] ?? null;
        $serviceId = $data['serviceId'] ?? null;

        return new self(
            name: Str::trimFromArray($data, 'name'),
            email: Str::trimFromArray($data, 'email'),
            role: Str::trimFromArray($data, 'role'),
            locale: Str::trimFromArray($data, 'locale', 'fr') ?: 'fr',
            password: '' === $password ? null : $password,
            managerId: is_numeric($managerId) ? (int) $managerId : null,
            agencyId: is_numeric($agencyId) ? (int) $agencyId : null,
            serviceId: is_numeric($serviceId) ? (int) $serviceId : null,
        );
    }
}
