<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Entity;

use Aurora\Module\Notes\Block\Repository\BlockNoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlockNoteRepository::class)]
#[ORM\Table(name: 'core_block_notes')]
#[ORM\Index(name: 'idx_block_notes_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_block_notes_parent', columns: ['parent_id'])]
class BlockNote extends AbstractBlockNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_block_note_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
