<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Repository;

use Aurora\Core\Auth\Entity\ResetPasswordRequest;
use Aurora\Core\Auth\Entity\ResetPasswordRequestInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\User\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<ResetPasswordRequestInterface>
 */
class ResetPasswordRequestRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class, ResetPasswordRequestInterface::class);
    }

    public function findBySelector(string $selector): ?ResetPasswordRequestInterface
    {
        return $this->findOneBy(['selector' => $selector]);
    }

    public function deleteByUser(User $user): void
    {
        $this->createQueryBuilder('r')
            ->delete()
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
