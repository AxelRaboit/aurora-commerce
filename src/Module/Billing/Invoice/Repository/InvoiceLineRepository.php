<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Repository;

use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLineInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<InvoiceLineInterface>
 */
class InvoiceLineRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceLine::class, InvoiceLineInterface::class);
    }
}
