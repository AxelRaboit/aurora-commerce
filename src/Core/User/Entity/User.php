<?php

declare(strict_types=1);

namespace Aurora\Core\User\Entity;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Enum\UserStatusEnum;
use Aurora\Core\User\Enum\UserTypeEnum;
use Aurora\Core\User\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_user_email_type', columns: ['email', 'type'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
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

    #[ORM\Column(length: 20, enumType: UserTypeEnum::class, options: ['default' => 'admin'])]
    #[Groups(['user:read'])]
    private UserTypeEnum $type = UserTypeEnum::Admin;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $profilePhotoPath = null;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $invitationSelector = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $invitationHashedToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $invitationExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:read'])]
    private ?DateTimeImmutable $invitedAt = null;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $emailVerificationExpiresAt = null;

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

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $token): static
    {
        $this->emailVerificationToken = $token;

        return $this;
    }

    public function getEmailVerificationExpiresAt(): ?DateTimeImmutable
    {
        return $this->emailVerificationExpiresAt;
    }

    public function setEmailVerificationExpiresAt(?DateTimeImmutable $expiresAt): static
    {
        $this->emailVerificationExpiresAt = $expiresAt;

        return $this;
    }

    public function getType(): UserTypeEnum
    {
        return $this->type;
    }

    public function setType(UserTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isAdmin(): bool
    {
        return UserTypeEnum::Admin === $this->type;
    }

    public function isFrontUser(): bool
    {
        return UserTypeEnum::FrontUser === $this->type;
    }

    public function getProfilePhotoPath(): ?string
    {
        return $this->profilePhotoPath;
    }

    public function setProfilePhotoPath(?string $profilePhotoPath): static
    {
        $this->profilePhotoPath = $profilePhotoPath;

        return $this;
    }

    public function getProfilePhotoUrl(): ?string
    {
        return null === $this->profilePhotoPath ? null : '/uploads/users/'.$this->profilePhotoPath;
    }

    public function eraseCredentials(): void {}
}
