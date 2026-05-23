<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Welding\Enum\WorkflowTemplateStatusEnum;
use Doctrine\Common\Collections\Collection;

interface WorkflowTemplateInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getTitle(): string;

    public function setTitle(string $title): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getApplicableTo(): ?string;

    public function setApplicableTo(?string $applicableTo): static;

    public function getVersion(): int;

    public function setVersion(int $version): static;

    public function getStatus(): WorkflowTemplateStatusEnum;

    public function setStatus(WorkflowTemplateStatusEnum $status): static;

    public function getParentVersion(): ?self;

    public function setParentVersion(?self $parentVersion): static;

    /** @return Collection<int, \Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface> */
    public function getSteps(): Collection;
}
