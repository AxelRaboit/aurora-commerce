<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Entity;

use Aurora\Module\Notes\PostIt\Repository\PostItNoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostItNoteRepository::class)]
#[ORM\Table(name: 'core_post_it_notes')]
#[ORM\Index(name: 'idx_post_it_notes_user', columns: ['user_id'])]
class PostItNote extends AbstractPostItNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_post_it_note_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
