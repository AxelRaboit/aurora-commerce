<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentTag\Entity;

use Aurora\Module\Ged\DocumentTag\Repository\DocumentTagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentTagRepository::class)]
#[ORM\Table(name: 'core_ged_document_tags')]
class DocumentTag extends AbstractDocumentTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_ged_document_tag_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
