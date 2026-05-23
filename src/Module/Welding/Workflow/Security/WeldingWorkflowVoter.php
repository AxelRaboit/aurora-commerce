<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Security;

use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Enum\UserRoleEnum;
use Aurora\Module\Welding\Workflow\Entity\WeldingWorkflowInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Gates fine-grained WeldingWorkflow / WeldingWorkflowStep actions on top of the
 * broad welding.workflows.* permissions. The permissions decide
 * *whether* a user can act on welding workflows at all; this voter
 * decides *which* workflow a specific user can act on.
 *
 * Rules:
 * - Dev / Admin → all actions on all workflows.
 * - SUBMIT_STEP → only the assignee (welder) of the workflow may submit
 *   their own steps. Prevents one welder from submitting another's work.
 * - VALIDATE_STEP / ARCHIVE / REJECT → permission-only (no per-user
 *   binding yet; V2 will bind validators by role).
 * - VIEW → anyone with the broad welding.workflows.view permission.
 */
final class WeldingWorkflowVoter extends Voter
{
    public const string VIEW = 'WELDING_WORKFLOW_VIEW';

    public const string SUBMIT_STEP = 'WELDING_STEP_SUBMIT';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::SUBMIT_STEP], true)) {
            return false;
        }

        return $subject instanceof WeldingWorkflowInterface || $subject instanceof WeldingWorkflowStepInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, [UserRoleEnum::Dev->value])
            || $this->accessDecisionManager->decide($token, [UserRoleEnum::Admin->value])) {
            return true;
        }

        $workflow = $subject instanceof WeldingWorkflowStepInterface ? $subject->getWorkflow() : $subject;
        if (!$workflow instanceof WeldingWorkflowInterface) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $user->hasPrivilege('welding.workflows.view'),
            self::SUBMIT_STEP => $user->hasPrivilege('welding.workflows.fill')
                && $this->isAssignee($workflow, $user),
            default => false,
        };
    }

    private function isAssignee(WeldingWorkflowInterface $workflow, User $user): bool
    {
        $assignee = $workflow->getAssignee();
        if (null === $assignee) {
            return false;
        }

        $assigneeUser = $assignee->getUser();

        return null !== $assigneeUser && $assigneeUser->getId() === $user->getId();
    }
}
