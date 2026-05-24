<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use {{NAMESPACE}}\Entity\{{NAME}};
use {{NAMESPACE}}\Entity\{{NAME}}Interface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<{{NAME}}Interface>
 */
class {{NAME}}Repository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, {{NAME}}::class, {{NAME}}Interface::class);
    }
}
