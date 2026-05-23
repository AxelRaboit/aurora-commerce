<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Entity;

use Aurora\Module\Welding\PdfDocument\Repository\WeldingPdfDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeldingPdfDocumentRepository::class)]
#[ORM\Table(name: 'core_welding_pdf_documents')]
class WeldingPdfDocument extends AbstractWeldingPdfDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_welding_pdf_document_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
