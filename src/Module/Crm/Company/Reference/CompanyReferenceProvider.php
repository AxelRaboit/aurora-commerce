<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Reference;

use Aurora\Core\Reference\EntityReferenceProviderInterface;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Aurora\Module\Crm\Company\Repository\CompanyRepository;

/**
 * Resolves `crm.company` soft references so other modules (Project, …) can
 * display / pick a linked company without importing Crm.
 */
final readonly class CompanyReferenceProvider implements EntityReferenceProviderInterface
{
    public function __construct(
        private CompanyRepository $companyRepository,
    ) {}

    public function getType(): string
    {
        return 'crm.company';
    }

    public function summarize(int $id): ?array
    {
        $company = $this->companyRepository->find($id);

        return $company instanceof CompanyInterface ? [
            'id' => $company->getId(),
            'name' => $company->getName(),
        ] : null;
    }

    public function options(): array
    {
        return array_map(
            static fn ($company): array => ['id' => (int) $company->getId(), 'name' => $company->getName()],
            $this->companyRepository->findAllOrderedByName(),
        );
    }
}
