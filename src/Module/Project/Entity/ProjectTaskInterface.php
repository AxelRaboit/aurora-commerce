<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;

interface ProjectTaskInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getReference(): ?string;

    public function setReference(?string $reference): static;

    public function getProject(): ProjectInterface;

    public function setProject(ProjectInterface $project): static;

    public function getTitle(): string;

    public function setTitle(string $title): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getColumn(): ProjectColumnInterface;

    public function setColumn(ProjectColumnInterface $column): static;

    public function getPriority(): ProjectTaskPriorityEnum;

    public function setPriority(ProjectTaskPriorityEnum $priority): static;

    public function getAssignee(): ?CoreUserInterface;

    public function setAssignee(?CoreUserInterface $assignee): static;

    public function getDueDate(): ?DateTimeImmutable;

    public function setDueDate(?DateTimeImmutable $dueDate): static;

    public function getPosition(): int;

    public function setPosition(int $position): static;

    public function getStoryPoints(): ?int;

    public function setStoryPoints(?int $storyPoints): static;

    public function getEstimateMinutes(): ?int;

    public function setEstimateMinutes(?int $estimateMinutes): static;

    /** @return Collection<int, ProjectLabelInterface> */
    public function getLabels(): Collection;

    public function addLabel(ProjectLabelInterface $label): static;

    public function removeLabel(ProjectLabelInterface $label): static;

    /** @return Collection<int, ProjectTaskItemInterface> */
    public function getItems(): Collection;

    /** @return Collection<int, ProjectTaskTimeEntryInterface> */
    public function getTimeEntries(): Collection;

    /** @return Collection<int, ProjectTaskCommentInterface> */
    public function getComments(): Collection;

    /** @return Collection<int, DocumentInterface> */
    public function getAttachments(): Collection;

    public function addAttachment(DocumentInterface $document): static;

    public function removeAttachment(DocumentInterface $document): static;

    /** @return Collection<int, CoreUserInterface> */
    public function getWatchers(): Collection;

    public function addWatcher(CoreUserInterface $user): static;

    public function removeWatcher(CoreUserInterface $user): static;

    public function getSprint(): ?ProjectSprintInterface;

    public function setSprint(?ProjectSprintInterface $sprint): static;
}
