<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Entity;

use Aurora\Module\PdfForm\PdfTemplateField\Repository\PdfTemplateFieldRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PdfTemplateFieldRepository::class)]
#[ORM\Table(name: 'core_pdfform_template_fields')]
class PdfTemplateField extends AbstractPdfTemplateField
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_pdfform_template_field_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
