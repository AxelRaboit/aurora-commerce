<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Entity;

use Aurora\Module\PdfForm\PdfTemplate\Repository\PdfTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PdfTemplateRepository::class)]
#[ORM\Table(name: 'core_pdfform_templates')]
class PdfTemplate extends AbstractPdfTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_pdfform_template_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
