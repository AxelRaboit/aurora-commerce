<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Doctrine\Common\Collections\Collection;

interface ProjectColumnInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getProject(): ProjectInterface;

    public function setProject(ProjectInterface $project): static;

    public function getLabel(): string;

    public function setLabel(string $label): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    /** @return Collection<int, ProjectTaskInterface> */
    public function getTasks(): Collection;
}
