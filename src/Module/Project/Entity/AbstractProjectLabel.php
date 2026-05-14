<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Timestampable\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractProjectLabel implements ProjectLabelInterface
{
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: ProjectInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ProjectInterface $project;

    #[ORM\Column(length: 60)]
    protected string $name;

    #[ORM\Column(length: 20)]
    protected string $color = 'accent';

    public function getProject(): ProjectInterface
    {
        return $this->project;
    }

    public function setProject(ProjectInterface $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }
}
