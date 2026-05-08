<?php

declare(strict_types=1);

namespace Aurora\Core\User\Entity;

use Aurora\Core\User\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'core_users')]
#[ORM\UniqueConstraint(name: 'uniq_user_email_type', columns: ['email', 'type'])]
class User extends AbstractUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_core_user_id', allocationSize: 1)]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subordinates')]
    #[ORM\JoinColumn(name: 'manager_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $manager = null;

    /** @var Collection<int, User> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'manager')]
    protected Collection $subordinates;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getManager(): ?CoreUserInterface
    {
        return $this->manager;
    }

    public function setManager(?CoreUserInterface $manager): static
    {
        $this->manager = $manager instanceof self ? $manager : null;

        return $this;
    }
}
