<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Entity;

use Aurora\Module\Welding\PdfTemplateField\Repository\WeldingPdfTemplateFieldRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeldingPdfTemplateFieldRepository::class)]
#[ORM\Table(name: 'core_welding_pdf_template_fields')]
class WeldingPdfTemplateField extends AbstractWeldingPdfTemplateField
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_welding_pdf_template_field_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
