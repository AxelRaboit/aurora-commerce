<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\Employee\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Hr\Employee\Dto\EmployeeInputFactoryInterface;
use Aurora\Module\Hr\Employee\Entity\EmployeeInterface;
use Aurora\Module\Hr\Employee\Manager\EmployeeManagerInterface;
use Aurora\Module\Hr\Employee\Serializer\EmployeeSerializerInterface;
use Aurora\Module\Hr\Employee\View\EmployeesViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/hr/employees', name: 'backend_hr_employees')]
#[IsGranted('hr.employees.view')]
class EmployeesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        protected readonly EmployeeSerializerInterface $employeeSerializer,
        protected readonly EmployeesViewBuilder $viewBuilder,
        protected readonly EmployeeManagerInterface $employeeManager,
        protected readonly EmployeeInputFactoryInterface $employeeInputFactory,
        protected readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $pagination = PaginationRequest::fromRequest($request);

        return $this->render('@Hr/backend/employees/index.html.twig', $this->viewBuilder->indexView($pagination));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request): JsonResponse
    {
        return $this->json($this->viewBuilder->buildListPayload(PaginationRequest::fromRequest($request)));
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('hr.employees.create')]
    public function create(Request $request): JsonResponse
    {
        $input = $this->employeeInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $employee = $this->employeeManager->create($input);

        return $this->jsonSuccess(['employee' => $this->employeeSerializer->serialize($employee)]);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('hr.employees.edit')]
    public function update(EmployeeInterface $employee, Request $request): JsonResponse
    {
        $input = $this->employeeInputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->employeeManager->update($employee, $input);

        return $this->jsonSuccess(['employee' => $this->employeeSerializer->serialize($employee)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('hr.employees.delete')]
    public function delete(EmployeeInterface $employee): JsonResponse
    {
        $this->employeeManager->delete($employee);

        return $this->jsonSuccess();
    }
}
