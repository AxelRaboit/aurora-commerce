<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;
use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateField;
use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateFieldInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<WeldingPdfTemplateFieldInterface> */
class WeldingPdfTemplateFieldRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeldingPdfTemplateField::class, WeldingPdfTemplateFieldInterface::class);
    }

    /** @return list<WeldingPdfTemplateFieldInterface> */
    public function findByTemplate(WeldingPdfTemplateInterface $template): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.template = :template')
            ->setParameter('template', $template)
            ->orderBy('f.position', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
