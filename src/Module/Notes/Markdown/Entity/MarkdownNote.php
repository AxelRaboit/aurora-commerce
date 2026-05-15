<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Markdown\Entity;

use Aurora\Module\Notes\Markdown\Repository\MarkdownNoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarkdownNoteRepository::class)]
#[ORM\Table(name: 'core_markdown_notes')]
#[ORM\Index(name: 'idx_markdown_notes_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_markdown_notes_parent', columns: ['parent_id'])]
class MarkdownNote extends AbstractMarkdownNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_markdown_note_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
