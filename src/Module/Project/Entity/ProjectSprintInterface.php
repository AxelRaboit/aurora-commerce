<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Contract\TimestampableInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;

interface ProjectSprintInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getProject(): ProjectInterface;

    public function setProject(ProjectInterface $project): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getStartDate(): ?DateTimeImmutable;

    public function setStartDate(?DateTimeImmutable $date): static;

    public function getEndDate(): ?DateTimeImmutable;

    public function setEndDate(?DateTimeImmutable $date): static;

    public function isActive(): bool;

    public function setIsActive(bool $active): static;

    /** @return Collection<int, ProjectTaskInterface> */
    public function getTasks(): Collection;
}
