<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Welding\Security;

use Aurora\Module\Hr\Employee\Entity\Employee;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Welding\Workflow\Entity\Workflow;
use Aurora\Module\Welding\Workflow\Security\WorkflowVoter;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStep;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[AllowMockObjectsWithoutExpectations]
final class WorkflowVoterTest extends TestCase
{
    private AccessDecisionManagerInterface $accessDecisionManager;
    private WorkflowVoter $voter;

    protected function setUp(): void
    {
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->voter = new WorkflowVoter($this->accessDecisionManager);
    }

    private function token(User $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function userWithId(int $id, array $privileges = []): User
    {
        $user = new User();
        $rp = new ReflectionProperty(User::class, 'id');
        $rp->setValue($user, $id);

        if ([] !== $privileges) {
            $rpp = new ReflectionProperty(User::class, 'cachedPrivileges');
            if ($rpp->isInitialized($user) || $rpp->getDefaultValue() !== null) {
                // ignore
            }
            // Use hasPrivilege public method indirectly through reflection if needed.
        }

        return $user;
    }

    private function workflowWithAssignee(?int $userId): Workflow
    {
        $workflow = new Workflow();
        if (null !== $userId) {
            $employee = new Employee();
            $employee->setUser($this->userWithId($userId));
            $workflow->setAssignee($employee);
        }

        return $workflow;
    }

    public function testDevBypassGrants(): void
    {
        $user = $this->userWithId(1);
        $this->accessDecisionManager->method('decide')->willReturn(true);

        $workflow = $this->workflowWithAssignee(null);
        $token = $this->token($user);

        $vote = new Vote();
        $result = $this->voter->vote($token, $workflow, [WorkflowVoter::SUBMIT_STEP], $vote);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testNonAssigneeIsDenied(): void
    {
        $user = $this->userWithId(1);
        $this->accessDecisionManager->method('decide')->willReturn(false);

        $workflow = $this->workflowWithAssignee(2);
        $token = $this->token($user);

        $vote = new Vote();
        $result = $this->voter->vote($token, $workflow, [WorkflowVoter::SUBMIT_STEP], $vote);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoterSupportsWorkflowStepSubject(): void
    {
        $user = $this->userWithId(1);
        $this->accessDecisionManager->method('decide')->willReturn(true); // grant via Dev

        $workflow = $this->workflowWithAssignee(1);
        $step = new WorkflowStep();
        $step->setWorkflow($workflow);

        $token = $this->token($user);
        $vote = new Vote();
        $result = $this->voter->vote($token, $step, [WorkflowVoter::SUBMIT_STEP], $vote);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUnknownAttributeAbstains(): void
    {
        $user = $this->userWithId(1);
        $workflow = $this->workflowWithAssignee(1);
        $token = $this->token($user);

        $vote = new Vote();
        $result = $this->voter->vote($token, $workflow, ['SOMETHING_ELSE'], $vote);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
