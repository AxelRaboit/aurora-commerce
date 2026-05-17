<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Platform\User\Entity\User;

interface ProjectSavedViewInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getOwner(): User;

    public function setOwner(User $user): static;

    public function getProject(): ProjectInterface;

    public function setProject(ProjectInterface $project): static;

    public function getName(): string;

    public function setName(string $name): static;

    /** @return array<mixed> */
    public function getFilters(): array;

    /** @param array<mixed> $filters */
    public function setFilters(array $filters): static;
}
