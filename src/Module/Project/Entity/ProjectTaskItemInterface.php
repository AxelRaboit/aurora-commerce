<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;

interface ProjectTaskItemInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getTask(): ProjectTaskInterface;

    public function setTask(ProjectTaskInterface $task): static;

    public function getLabel(): string;

    public function setLabel(string $label): static;

    public function isDone(): bool;

    public function setDone(bool $done): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;
}
