<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Serializer;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface;

interface PersonalFinanceBudgetItemSerializerInterface
{
    /**
     * Serializes a budget item. The optional `$actual` parameter passes
     * the computed actual (sum of transactions in the month for the
     * item's category) — when present, the response also includes
     * `expected`, `actual`, `diff` for the budget UI to render
     * progress bars without re-computing on the client.
     *
     * @return array<string, mixed>
     */
    public function serialize(PersonalFinanceBudgetItemInterface $item, ?string $actual = null): array;
}
