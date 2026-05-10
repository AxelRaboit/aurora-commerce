<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateField;
use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateFieldInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PdfTemplateFieldInterface> */
class PdfTemplateFieldRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PdfTemplateField::class, PdfTemplateFieldInterface::class);
    }

    /** @return list<PdfTemplateFieldInterface> */
    public function findByTemplate(PdfTemplateInterface $template): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.template = :template')
            ->setParameter('template', $template)
            ->orderBy('f.position', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
