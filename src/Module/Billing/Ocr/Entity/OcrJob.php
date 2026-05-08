<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Entity;

use Aurora\Module\Billing\Ocr\Repository\OcrJobRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OcrJobRepository::class)]
#[ORM\Table(name: 'core_billing_ocr_jobs')]
#[ORM\Index(name: 'idx_billing_ocr_status', columns: ['status'])]
class OcrJob extends AbstractOcrJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_ocr_job_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
