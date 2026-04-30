<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Crm\Company\Contract\CompanyManagerInterface;
use Aurora\Module\Crm\Company\DTO\CompanyInput;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Company\Serializer\CompanySerializer;
use Aurora\Module\Crm\Company\View\CompaniesViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/crm/companies', name: 'crm_companies')]
#[IsGranted('crm.companies.manage')]
final class CompaniesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly CompanySerializer $companySerializer,
        private readonly CompanyManagerInterface $companyManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly CompaniesViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->render('@Crm/admin/companies/index.html.twig', $this->viewBuilder->indexView($pagination));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        return $this->json($this->viewBuilder->buildListPayload($pagination));
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = CompanyInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $company = $this->companyManager->create($input);

        return $this->jsonSuccess(['company' => $this->companySerializer->serialize($company)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Company $company, Request $request): JsonResponse
    {
        $input = CompanyInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $this->companyManager->update($company, $input);

        return $this->jsonSuccess(['company' => $this->companySerializer->serialize($company)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Company $company): JsonResponse
    {
        $this->companyManager->delete($company);

        return $this->jsonSuccess();
    }
}
