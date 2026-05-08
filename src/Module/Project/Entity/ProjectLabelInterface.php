<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Contract\TimestampableInterface;

interface ProjectLabelInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getProject(): ProjectInterface;

    public function setProject(ProjectInterface $project): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getColor(): string;

    public function setColor(string $color): static;
}
