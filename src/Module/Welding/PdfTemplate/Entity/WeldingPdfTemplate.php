<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Entity;

use Aurora\Module\Welding\PdfTemplate\Repository\WeldingPdfTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeldingPdfTemplateRepository::class)]
#[ORM\Table(name: 'core_welding_pdf_templates')]
class WeldingPdfTemplate extends AbstractWeldingPdfTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_welding_pdf_template_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
