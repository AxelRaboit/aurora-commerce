<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\Workflow\Security\WeldingWorkflowVoter;
use Aurora\Module\Welding\WorkflowStep\Dto\WeldingWorkflowStepValidationInputFactoryInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;
use Aurora\Module\Welding\WorkflowStep\Manager\WeldingWorkflowStepManagerInterface;
use Aurora\Module\Welding\WorkflowStep\Serializer\WeldingWorkflowStepSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/workflow-steps', name: 'backend_welding_workflow_steps')]
#[IsGranted('welding.workflows.view')]
class WeldingWorkflowStepsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly WeldingWorkflowStepSerializerInterface $serializer,
        protected readonly WeldingWorkflowStepManagerInterface $manager,
        protected readonly WeldingWorkflowStepValidationInputFactoryInterface $validationFactory,
        protected readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/{id}/submit', name: '_submit', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted(WeldingWorkflowVoter::SUBMIT_STEP, subject: 'step')]
    public function submit(WeldingWorkflowStepInterface $step): JsonResponse
    {
        $welder = $this->getUser();
        if (!$welder instanceof CoreUserInterface) {
            return $this->jsonFailure('backend.welding.workflow_steps.errors.unauthenticated');
        }

        $this->manager->submit($step, $welder);

        return $this->jsonSuccess(['step' => $this->serializer->serialize($step)]);
    }

    #[Route('/{id}/validate', name: '_validate', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflows.validate')]
    public function validate(WeldingWorkflowStepInterface $step, Request $request): JsonResponse
    {
        $validator = $this->getUser();
        if (!$validator instanceof CoreUserInterface) {
            return $this->jsonFailure('backend.welding.workflow_steps.errors.unauthenticated');
        }

        $input = $this->validationFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->manager->recordValidation($step, $validator, $input);

        return $this->jsonSuccess(['step' => $this->serializer->serialize($step)]);
    }
}
