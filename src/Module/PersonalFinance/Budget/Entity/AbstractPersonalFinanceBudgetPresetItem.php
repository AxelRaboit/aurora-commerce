<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractPersonalFinanceBudgetPresetItem implements PersonalFinanceBudgetPresetItemInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: PersonalFinanceBudgetPresetInterface::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'preset_id', nullable: false, onDelete: 'CASCADE')]
    protected PersonalFinanceBudgetPresetInterface $preset;

    #[ORM\Column(length: 16, enumType: PersonalFinanceBudgetSectionEnum::class)]
    protected PersonalFinanceBudgetSectionEnum $section;

    #[ORM\Column(length: 120)]
    protected string $label;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    protected string $plannedAmount = '0.00';

    #[ORM\ManyToOne(targetEntity: PersonalFinanceCategoryInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?PersonalFinanceCategoryInterface $category = null;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $notes = null;

    public function getPreset(): PersonalFinanceBudgetPresetInterface
    {
        return $this->preset;
    }

    public function setPreset(PersonalFinanceBudgetPresetInterface $preset): static
    {
        $this->preset = $preset;

        return $this;
    }

    public function getSection(): PersonalFinanceBudgetSectionEnum
    {
        return $this->section;
    }

    public function setSection(PersonalFinanceBudgetSectionEnum $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPlannedAmount(): string
    {
        return $this->plannedAmount;
    }

    public function setPlannedAmount(string $plannedAmount): static
    {
        $this->plannedAmount = $plannedAmount;

        return $this;
    }

    public function getCategory(): ?PersonalFinanceCategoryInterface
    {
        return $this->category;
    }

    public function setCategory(?PersonalFinanceCategoryInterface $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
