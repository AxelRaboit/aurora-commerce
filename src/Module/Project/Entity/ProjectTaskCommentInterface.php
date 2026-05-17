<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Core\Platform\User\Entity\User;

interface ProjectTaskCommentInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getTask(): ProjectTaskInterface;

    public function setTask(ProjectTaskInterface $task): static;

    public function getAuthor(): User;

    public function setAuthor(User $author): static;

    public function getContent(): string;

    public function setContent(string $content): static;
}
