<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Enum\UserTypeEnum;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Resolves admin/backend users only (type = admin).
 *
 * @implements UserProviderInterface<User>
 */
final readonly class AdminUserProvider implements UserProviderInterface
{
    public function __construct(private UserRepository $userRepository) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneBy(['email' => $identifier, 'type' => UserTypeEnum::Admin]);
        if (null === $user) {
            throw new UserNotFoundException(sprintf('Admin user "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
