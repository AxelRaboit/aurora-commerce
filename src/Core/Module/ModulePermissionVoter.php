<?php

declare(strict_types=1);

namespace Aurora\Core\Module;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Resolves module-defined permission strings (e.g. "crm.contacts.view")
 * to the role check declared in each module's getPermissions() list.
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
        $requiredRole = $this->permissionRegistry->getRequiredRole($attribute);

        return null !== $requiredRole && $this->security->decide($token, [$requiredRole]);
    }
}
