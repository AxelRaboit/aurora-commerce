<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\Dto;

use Aurora\Module\Planning\Planning\Enum\PlanningVisibilityEnum;
use Symfony\Component\Validator\Constraints as Assert;

class PlanningInput implements PlanningInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.plannings.errors.name_required')]
        #[Assert\Length(max: 150)]
        public readonly string $name = '',
        public readonly ?string $description = null,
        #[Assert\NotBlank(message: 'backend.plannings.errors.color_required')]
        #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'backend.plannings.errors.color_invalid')]
        public readonly string $color = '#3b82f6',
        #[Assert\NotBlank(message: 'backend.plannings.errors.timezone_required')]
        #[Assert\Length(max: 64)]
        public readonly string $timezone = 'Europe/Paris',
        #[Assert\NotBlank(message: 'backend.plannings.errors.visibility_required')]
        #[Assert\Choice(callback: [PlanningVisibilityEnum::class, 'values'], message: 'backend.plannings.errors.visibility_invalid')]
        public readonly string $visibility = PlanningVisibilityEnum::Private_->value,
        #[Assert\Positive]
        public readonly ?int $ownerId = null,
        #[Assert\Positive]
        public readonly ?int $agencyId = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function getVisibilityEnum(): PlanningVisibilityEnum
    {
        return PlanningVisibilityEnum::from($this->visibility);
    }

    public function getOwnerId(): ?int
    {
        return $this->ownerId;
    }

    public function getAgencyId(): ?int
    {
        return $this->agencyId;
    }
}
