<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Welding\WorkflowStep\Dto\WorkflowStepValidationInputFactoryInterface;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStepInterface;
use Aurora\Module\Welding\WorkflowStep\Manager\WorkflowStepManagerInterface;
use Aurora\Module\Welding\WorkflowStep\Serializer\WorkflowStepSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/welding/workflow-steps', name: 'backend_welding_workflow_steps')]
#[IsGranted('welding.workflows.view')]
class WorkflowStepsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly WorkflowStepSerializerInterface $serializer,
        protected readonly WorkflowStepManagerInterface $manager,
        protected readonly WorkflowStepValidationInputFactoryInterface $validationFactory,
        protected readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/{id}/submit', name: '_submit', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('welding.workflows.fill')]
    public function submit(WorkflowStepInterface $step): JsonResponse
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
    public function validate(WorkflowStepInterface $step, Request $request): JsonResponse
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
