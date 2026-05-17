<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Auth\Entity;

use Aurora\Core\Platform\Auth\Repository\ResetPasswordRequestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
#[ORM\Table(name: 'core_reset_password_requests')]
class ResetPasswordRequest extends AbstractResetPasswordRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_reset_password_request_id', allocationSize: 1)]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
