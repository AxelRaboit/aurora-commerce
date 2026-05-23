<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceCategorizationRuleInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getUser(): CoreUserInterface;

    public function setUser(CoreUserInterface $user): static;

    public function getCategory(): PersonalFinanceCategoryInterface;

    public function setCategory(PersonalFinanceCategoryInterface $category): static;

    public function getPattern(): string;

    public function setPattern(string $pattern): static;

    public function getHits(): int;

    public function setHits(int $hits): static;

    public function incrementHits(): static;
}
