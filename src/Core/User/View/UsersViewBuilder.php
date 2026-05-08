<?php

declare(strict_types=1);

namespace Aurora\Core\User\View;

use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Agency\Repository\AgencyRepository;
use Aurora\Core\Module\PermissionRegistry;
use Aurora\Core\Service\Entity\ServiceInterface;
use Aurora\Core\Service\Repository\ServiceRepository;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class UsersViewBuilder
{
    /** @var array<string, ApplicationParameterEnum> module ID → admin-enabled toggle, built from the enum */
    private array $moduleToggles;

    public function __construct(
        private PermissionRegistry $permissionRegistry,
        private SettingRepository $settingRepository,
        private AgencyRepository $agencyRepository,
        private ServiceRepository $serviceRepository,
        private TranslatorInterface $translator,
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

        $translator = $this->translator;
        $roles = array_map(
            static fn (UserRoleEnum $role): array => ['value' => $role->value, 'label' => $translator->trans($role->getLabelKey())],
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

        $agencies = array_map(
            static fn (AgencyInterface $agency): array => ['value' => (string) $agency->getId(), 'label' => $agency->getName()],
            $this->agencyRepository->findAllAlphabetical(),
        );

        $services = array_map(
            static fn (ServiceInterface $service): array => ['value' => (string) $service->getId(), 'label' => $service->getName()],
            $this->serviceRepository->findAllAlphabetical(),
        );

        return [
            'roles' => $roles,
            'isDev' => $isDev,
            'currentUserPriority' => $currentUserPriority,
            'privilegesByModule' => $privilegesByModule,
            'agencies' => $agencies,
            'services' => $services,
        ];
    }
}
