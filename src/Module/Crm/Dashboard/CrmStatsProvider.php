<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Dashboard;

use Aurora\Core\Dashboard\DashboardStatsProviderInterface;
use Aurora\Module\Crm\Company\Repository\CompanyRepository;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Aurora\Module\Crm\Deal\Repository\DealRepository;

/**
 * CRM slice of the backend dashboard. Lives in the Crm module so the General
 * dashboard never imports CRM repositories.
 */
final readonly class CrmStatsProvider implements DashboardStatsProviderInterface
{
    public function __construct(
        private ContactRepository $contactRepository,
        private CompanyRepository $companyRepository,
        private DealRepository $dealRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'crm';
    }

    public function getStats(): array
    {
        $byStage = $this->dealRepository->countByStage();
        $stages = [];
        foreach (DealStageEnum::cases() as $stage) {
            $stages[] = ['stage' => $stage->value, 'count' => $byStage[$stage->value] ?? 0];
        }

        return [
            'crm' => [
                'contacts' => $this->contactRepository->count([]),
                'companies' => $this->companyRepository->count([]),
                'deals' => array_sum($byStage),
                'dealsByStage' => $stages,
                'pipelineValue' => $this->dealRepository->getTotalValue(),
                'wonValue' => $this->dealRepository->getTotalValue(DealStageEnum::Won),
                'recentDeals' => $this->dealRepository->findRecent(5),
            ],
        ];
    }
}
