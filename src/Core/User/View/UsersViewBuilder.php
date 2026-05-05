<?php

declare(strict_types=1);

namespace Aurora\Core\User\View;

use Aurora\Core\Module\PermissionRegistry;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;

final readonly class UsersViewBuilder
{
    /** @var array<string, ApplicationParameterEnum> module ID → admin-enabled toggle, built from the enum */
    private array $moduleToggles;

    public function __construct(
        private PermissionRegistry $permissionRegistry,
        private SettingRepository $settingRepository,
    ) {
        $toggles = [];
        foreach (ApplicationParameterEnum::cases() as $case) {
            $moduleId = $case->getModuleId();
            if (null !== $moduleId) {
                $toggles[$moduleId] = $case;
            }
        }

        $this->moduleToggles = $toggles;
    }

    /**
     * @return array<string, mixed>
     */
    public function indexView(bool $isDev, ?User $currentUser): array
    {
        $selectableRoles = $isDev
            ? [UserRoleEnum::Dev, ...UserRoleEnum::selectableForAdmin()]
            : UserRoleEnum::selectableForAdmin();

        $roles = array_map(
            static fn (UserRoleEnum $role): array => ['value' => $role->value, 'label' => $role->label()],
            $selectableRoles,
        );

        $currentUserPriority = $currentUser instanceof User
            ? UserRoleEnum::highestPriorityForRoles($currentUser->getRoles())
            : 0;

        // All privileges grouped by module, filtered to only enabled modules.
        $privilegesByModule = [];
        foreach ($this->permissionRegistry->byModule() as $moduleId => $privileges) {
            if ([] === $privileges) {
                continue;
            }

            $toggle = $this->moduleToggles[$moduleId] ?? null;
            if (null !== $toggle && !$this->settingRepository->getBoolean($toggle->value, true)) {
                continue;
            }

            $privilegesByModule[] = [
                'module' => $moduleId,
                'privileges' => $privileges,
            ];
        }

        return [
            'roles' => $roles,
            'isDev' => $isDev,
            'currentUserPriority' => $currentUserPriority,
            'privilegesByModule' => $privilegesByModule,
        ];
    }
}
