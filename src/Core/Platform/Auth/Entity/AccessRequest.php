<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Auth\Entity;

use Aurora\Core\Platform\Auth\Repository\AccessRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AccessRequestRepository::class)]
#[ORM\Table(name: 'core_access_requests')]
#[ORM\Index(name: 'IDX_access_request_token', columns: ['token'])]
#[ORM\Index(name: 'IDX_access_request_status', columns: ['status'])]
class AccessRequest extends AbstractAccessRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_access_request_id', allocationSize: 1)]
    #[ORM\Column]
    #[Groups(['access_request:read'])]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
