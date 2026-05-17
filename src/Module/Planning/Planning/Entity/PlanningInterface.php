<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\Entity;

use Aurora\Core\Platform\Agency\Entity\AgencyInterface;
use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Planning\Event\Entity\PlanningEventInterface;
use Aurora\Module\Planning\Planning\Enum\PlanningVisibilityEnum;
use Doctrine\Common\Collections\Collection;

interface PlanningInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getName(): string;

    public function setName(string $name): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getColor(): string;

    public function setColor(string $color): static;

    public function getTimezone(): string;

    public function setTimezone(string $timezone): static;

    public function getVisibility(): PlanningVisibilityEnum;

    public function setVisibility(PlanningVisibilityEnum $visibility): static;

    public function getOwner(): ?CoreUserInterface;

    public function setOwner(?CoreUserInterface $owner): static;

    public function getAgency(): ?AgencyInterface;

    public function setAgency(?AgencyInterface $agency): static;

    /** @return Collection<int, PlanningEventInterface> */
    public function getEvents(): Collection;
}
