<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;

interface ProjectTaskTimeEntryInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getTask(): ProjectTaskInterface;

    public function setTask(ProjectTaskInterface $task): static;

    public function getUser(): User;

    public function setUser(User $user): static;

    public function getMinutes(): int;

    public function setMinutes(int $minutes): static;

    public function getNote(): ?string;

    public function setNote(?string $note): static;

    public function getLoggedAt(): DateTimeImmutable;

    public function setLoggedAt(DateTimeImmutable $loggedAt): static;
}
