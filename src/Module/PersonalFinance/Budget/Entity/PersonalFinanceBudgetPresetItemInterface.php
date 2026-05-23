<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;

interface PersonalFinanceBudgetPresetItemInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getPreset(): PersonalFinanceBudgetPresetInterface;

    public function setPreset(PersonalFinanceBudgetPresetInterface $preset): static;

    public function getSection(): PersonalFinanceBudgetSectionEnum;

    public function setSection(PersonalFinanceBudgetSectionEnum $section): static;

    public function getLabel(): string;

    public function setLabel(string $label): static;

    public function getPlannedAmount(): string;

    public function setPlannedAmount(string $plannedAmount): static;

    public function getCategory(): ?PersonalFinanceCategoryInterface;

    public function setCategory(?PersonalFinanceCategoryInterface $category): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getNotes(): ?string;

    public function setNotes(?string $notes): static;
}
