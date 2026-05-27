<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultUserConfig\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Tools\Vault\VaultUserConfig\Entity\VaultUserConfig;
use Aurora\Module\Tools\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<VaultUserConfigInterface> */
class VaultUserConfigRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VaultUserConfig::class, VaultUserConfigInterface::class);
    }

    public function findOneByUser(CoreUserInterface $user): ?VaultUserConfigInterface
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
