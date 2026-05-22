<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Security;

use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletMemberRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Enum\UserRoleEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Authorizes operations on a PersonalFinance Wallet based on the
 * acting user's membership role on that wallet. Reused across all
 * PersonalFinance sub-modules (Transaction, Budget, Goal, …) to gate
 * write operations on the parent wallet.
 */
final class PersonalFinanceWalletVoter extends Voter
{
    public const string VIEW = 'FINANCE_WALLET_VIEW';

    public const string EDIT = 'FINANCE_WALLET_EDIT';

    public const string EDIT_TRANSACTIONS = 'FINANCE_WALLET_EDIT_TX';

    public const string MANAGE_MEMBERS = 'FINANCE_WALLET_MEMBERS';

    public const string DELETE = 'FINANCE_WALLET_DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly PersonalFinanceWalletMemberRepository $memberRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::EDIT_TRANSACTIONS, self::MANAGE_MEMBERS, self::DELETE], true)) {
            return false;
        }

        return $subject instanceof PersonalFinanceWalletInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof PersonalFinanceWalletInterface) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, [UserRoleEnum::Dev->value])
            || $this->accessDecisionManager->decide($token, [UserRoleEnum::Admin->value])) {
            return true;
        }

        $member = $this->memberRepository->findOneByWalletAndUser($subject, $user);
        if (!$member instanceof PersonalFinanceWalletMemberInterface) {
            return false;
        }

        $role = $member->getRole();

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT_TRANSACTIONS => $role->canEdit(),
            self::EDIT, self::MANAGE_MEMBERS, self::DELETE => PersonalFinanceWalletRoleEnum::Owner === $role,
            default => false,
        };
    }
}
