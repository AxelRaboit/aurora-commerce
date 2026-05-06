<?php

declare(strict_types=1);

namespace Aurora\Core\Frontend\Service;

use Aurora\Core\Frontend\Contract\FrontInterface;

final readonly class FrontRegistry
{
    /** @var list<FrontInterface> */
    private array $fronts;

    /** @param iterable<FrontInterface> $fronts */
    public function __construct(iterable $fronts)
    {
        $sorted = iterator_to_array($fronts, false);
        usort($sorted, static fn (FrontInterface $a, FrontInterface $b): int => $b->getPriority() <=> $a->getPriority());
        $this->fronts = $sorted;
    }

    /** @return list<FrontInterface> */
    public function all(): array
    {
        return $this->fronts;
    }

    public function find(string $slug): ?FrontInterface
    {
        foreach ($this->fronts as $front) {
            if ($front->getSlug() === $slug) {
                return $front;
            }
        }

        return null;
    }

    public function highest(): ?FrontInterface
    {
        return $this->fronts[0] ?? null;
    }
}
