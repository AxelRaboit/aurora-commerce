<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\LocaleEnum;
use App\Enum\UserRoleEnum;
use App\Enum\UserStatusEnum;
use App\Repository\UserRepository;
use App\Trait\TimestampableTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read'])]
    private string $email;

    #[ORM\Column(length: 100)]
    #[Groups(['user:read'])]
    private string $name;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    #[ORM\Column(length: 5, enumType: LocaleEnum::class)]
    #[Groups(['user:read'])]
    private LocaleEnum $locale = LocaleEnum::French;

    #[ORM\Column(length: 20, enumType: UserStatusEnum::class, options: ['default' => 'active'])]
    #[Groups(['user:read'])]
    private UserStatusEnum $status = UserStatusEnum::Active;

    #[ORM\Column(length: 20, nullable: true, unique: true)]
    private ?string $invitationSelector = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $invitationHashedToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $invitationExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:read'])]
    private ?DateTimeImmutable $invitedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = UserRoleEnum::User->value;

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getLocale(): LocaleEnum
    {
        return $this->locale;
    }

    public function setLocale(LocaleEnum $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getStatus(): UserStatusEnum
    {
        return $this->status;
    }

    public function setStatus(UserStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return UserStatusEnum::Active === $this->status;
    }

    public function isInvited(): bool
    {
        return UserStatusEnum::Invited === $this->status;
    }

    public function getInvitationSelector(): ?string
    {
        return $this->invitationSelector;
    }

    public function setInvitationSelector(?string $selector): static
    {
        $this->invitationSelector = $selector;

        return $this;
    }

    public function getInvitationHashedToken(): ?string
    {
        return $this->invitationHashedToken;
    }

    public function setInvitationHashedToken(?string $hashedToken): static
    {
        $this->invitationHashedToken = $hashedToken;

        return $this;
    }

    public function getInvitationExpiresAt(): ?DateTimeImmutable
    {
        return $this->invitationExpiresAt;
    }

    public function setInvitationExpiresAt(?DateTimeImmutable $expiresAt): static
    {
        $this->invitationExpiresAt = $expiresAt;

        return $this;
    }

    public function getInvitedAt(): ?DateTimeImmutable
    {
        return $this->invitedAt;
    }

    public function setInvitedAt(?DateTimeImmutable $invitedAt): static
    {
        $this->invitedAt = $invitedAt;

        return $this;
    }

    public function eraseCredentials(): void {}
}
