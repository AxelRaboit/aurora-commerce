<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Entity;

use Aurora\Module\Ged\Document\Repository\DocumentRepository;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: 'core_ged_documents')]
class Document extends AbstractDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_ged_document_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    /** @var Collection<int, DocumentTagInterface> */
    #[ORM\ManyToMany(targetEntity: DocumentTagInterface::class)]
    #[ORM\JoinTable(
        name: 'core_ged_document_tag_map',
        joinColumns: [new ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'document_tag_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
    )]
    protected Collection $tags;

    public function __construct()
    {
        parent::__construct();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
