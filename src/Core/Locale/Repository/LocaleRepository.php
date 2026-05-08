<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\Repository;

use Aurora\Core\Locale\Entity\Locale;
use Aurora\Core\Locale\Entity\LocaleInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<LocaleInterface>
 */
class LocaleRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Locale::class, LocaleInterface::class);
    }
}
