<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\Entity;

use Aurora\Module\PdfForm\PdfDocument\Repository\PdfDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PdfDocumentRepository::class)]
#[ORM\Table(name: 'core_pdfform_documents')]
class PdfDocument extends AbstractPdfDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_pdfform_document_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
