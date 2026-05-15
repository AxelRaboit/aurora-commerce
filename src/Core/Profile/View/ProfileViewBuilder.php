<?php

declare(strict_types=1);

namespace Aurora\Core\Profile\View;

use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Repository\UserRepository;

/**
 * Builds the Twig payload for the admin profile page. Currently exposes the
 * mood-message length cap, kept as a service so future profile widgets can
 * grow without re-introducing payload logic in the controller.
 */
final readonly class ProfileViewBuilder
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(User $user): array
    {
        return [
            'moodMessageMaxLength' => User::MOOD_MESSAGE_MAX_LENGTH,
            'canDeleteAccount' => !$this->isLastDevOfType($user),
        ];
    }

    private function isLastDevOfType(User $user): bool
    {
        if (!\in_array(UserRoleEnum::Dev->value, $user->getRoles(), true)) {
            return false;
        }

        return 1 === $this->userRepository->countByRoleAndType(UserRoleEnum::Dev->value, $user->getType());
    }
}
