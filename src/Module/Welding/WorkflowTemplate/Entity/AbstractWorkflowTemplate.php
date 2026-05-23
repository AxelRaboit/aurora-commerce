<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Aurora\Module\Welding\Enum\WorkflowTemplateStatusEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractWorkflowTemplate implements WorkflowTemplateInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 200)]
    protected string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $applicableTo = null;

    #[ORM\Column(options: ['default' => 1])]
    protected int $version = 1;

    #[ORM\Column(length: 20, enumType: WorkflowTemplateStatusEnum::class, options: ['default' => 'draft'])]
    protected WorkflowTemplateStatusEnum $status = WorkflowTemplateStatusEnum::Draft;

    #[ORM\ManyToOne(targetEntity: WorkflowTemplateInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?WorkflowTemplateInterface $parentVersion = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getApplicableTo(): ?string
    {
        return $this->applicableTo;
    }

    public function setApplicableTo(?string $applicableTo): static
    {
        $this->applicableTo = $applicableTo;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getStatus(): WorkflowTemplateStatusEnum
    {
        return $this->status;
    }

    public function setStatus(WorkflowTemplateStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getParentVersion(): ?WorkflowTemplateInterface
    {
        return $this->parentVersion;
    }

    public function setParentVersion(?WorkflowTemplateInterface $parentVersion): static
    {
        $this->parentVersion = $parentVersion;

        return $this;
    }
}
