<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class PersonalFinanceModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private PersonalFinanceContext $personalFinanceContext) {}

    public function getId(): string
    {
        return 'personal_finance';
    }

    public function getPermissions(): array
    {
        return [];
    }

    public function getNavSections(): array
    {
        if (!$this->personalFinanceContext->isBackendEnabled()) {
            return [];
        }

        return [];
    }

    public function getCatalogNavSections(): array
    {
        return [];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::PersonalFinanceBackend->toToggle(),
        ];
    }
}
