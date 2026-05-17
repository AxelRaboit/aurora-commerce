<?php

declare(strict_types=1);

namespace Aurora\Core\Module\Security;

use Aurora\Core\Module\Service\PermissionRegistry;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Enum\UserRoleEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Resolves module privilege strings (e.g. "crm.contacts.view") to an access decision.
 *
 * Rules:
 *   - ROLE_DEV  → always granted (bypass everything)
 *   - ROLE_ADMIN → always granted (full access)
 *   - ROLE_USER  → granted only if the privilege is in the user's explicit privileges list
 */
final class ModulePermissionVoter extends Voter
{
    public function __construct(
        private readonly PermissionRegistry $permissionRegistry,
        private readonly AccessDecisionManagerInterface $security,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->permissionRegistry->has($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if ($this->security->decide($token, [UserRoleEnum::Dev->value])) {
            return true;
        }

        if ($this->security->decide($token, [UserRoleEnum::Admin->value])) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $user->hasPrivilege($attribute);
    }
}
