<?php

declare(strict_types=1);

namespace Aurora\Core\General\Dashboard\View;

use Aurora\Core\General\Dashboard\Service\StatsService;
use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;

final readonly class DashboardViewBuilder
{
    /** @var array<string, ModuleParameterEnum> */
    private const array DASHBOARD_MODULES = [
        'editorial' => ModuleParameterEnum::EditorialBackend,
        'crm' => ModuleParameterEnum::CrmBackend,
        'erp' => ModuleParameterEnum::ErpBackend,
        'billing' => ModuleParameterEnum::BillingBackend,
        'ecommerce' => ModuleParameterEnum::EcommerceBackend,
        'photo' => ModuleParameterEnum::PhotoBackend,
    ];

    public function __construct(
        private StatsService $statsService,
        private ModuleAccessChecker $moduleAccessChecker,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        $enabledModules = $this->buildEnabledModules();
        $enabledModuleIds = array_keys(array_filter($enabledModules));

        return [
            'enabledModules' => $enabledModules,
            'stats' => $this->statsService->getStats($enabledModuleIds),
        ];
    }

    /** @return array<string, bool> */
    private function buildEnabledModules(): array
    {
        $result = [];
        foreach (self::DASHBOARD_MODULES as $moduleId => $toggle) {
            $result[$moduleId] = $this->moduleAccessChecker->isEnabled($toggle);
        }

        return $result;
    }
}
