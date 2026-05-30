<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Reference;

use Aurora\Core\Reference\EntityReferenceProviderInterface;
use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Crm\Deal\Repository\DealRepository;

/**
 * Resolves `crm.deal` soft references so other modules (Project, …) can
 * display / pick a linked deal without importing Crm.
 */
final readonly class DealReferenceProvider implements EntityReferenceProviderInterface
{
    public function __construct(
        private DealRepository $dealRepository,
    ) {}

    public function getType(): string
    {
        return 'crm.deal';
    }

    public function summarize(int $id): ?array
    {
        $deal = $this->dealRepository->find($id);

        return $deal instanceof DealInterface ? [
            'id' => $deal->getId(),
            'name' => $deal->getName(),
        ] : null;
    }

    public function options(): array
    {
        return array_map(
            static fn ($deal): array => ['id' => (int) $deal->getId(), 'name' => $deal->getName()],
            $this->dealRepository->findAllOrderedByName(),
        );
    }
}
