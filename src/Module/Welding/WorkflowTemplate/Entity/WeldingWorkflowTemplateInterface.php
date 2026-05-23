<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Module\Welding\Enum\WeldingWorkflowTemplateStatusEnum;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use Doctrine\Common\Collections\Collection;

interface WeldingWorkflowTemplateInterface extends TimestampableInterface
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

    public function getStatus(): WeldingWorkflowTemplateStatusEnum;

    public function setStatus(WeldingWorkflowTemplateStatusEnum $status): static;

    public function getParentVersion(): ?self;

    public function setParentVersion(?self $parentVersion): static;

    /** @return Collection<int, WeldingWorkflowStepTemplateInterface> */
    public function getSteps(): Collection;
}
