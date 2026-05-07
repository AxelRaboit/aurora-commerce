<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Entity;

use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Repository\ProjectTaskCommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectTaskCommentRepository::class)]
#[ORM\Table(name: 'core_project_task_comments')]
#[ORM\HasLifecycleCallbacks]
class ProjectTaskComment
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_project_task_comment_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProjectTask::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProjectTask $task;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTask(): ProjectTask
    {
        return $this->task;
    }

    public function setTask(ProjectTask $task): static
    {
        $this->task = $task;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }
}
