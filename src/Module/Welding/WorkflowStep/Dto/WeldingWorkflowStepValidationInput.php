<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class WeldingWorkflowStepValidationInput implements WeldingWorkflowStepValidationInputInterface
{
    public const string DECISION_VALIDATE = 'validate';

    public const string DECISION_REJECT = 'reject';

    public function __construct(
        #[Assert\Choice(choices: [self::DECISION_VALIDATE, self::DECISION_REJECT], message: 'backend.welding.workflow_steps.errors.decision_invalid')]
        public readonly string $decision = self::DECISION_VALIDATE,
        public readonly ?string $comment = null,
    ) {}

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}
