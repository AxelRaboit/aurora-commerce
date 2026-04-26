<?php

declare(strict_types=1);

namespace App\Module\Crm\Company\Controller\Admin;

use App\Core\Enum\HttpMethodEnum;
use App\Core\Frontend\Controller\JsonRequestTrait;
use App\Core\Validation\DTO\PaginationRequest;
use App\Core\Validation\Service\PayloadValidator;
use App\Module\Crm\Company\Contract\CompanyManagerInterface;
use App\Module\Crm\Company\DTO\CompanyInput;
use App\Module\Crm\Company\Entity\Company;
use App\Module\Crm\Company\Repository\CompanyRepository;
use App\Module\Crm\Company\Serializer\CompanySerializer;
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

    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySerializer $companySerializer,
        private readonly CompanyManagerInterface $companyManager,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->render('@Crm/admin/companies/index.html.twig', [
            'companies' => $this->buildListPayload($pagination),
            'search' => $pagination->search ?? '',
            'createPath' => $this->generateUrl('crm_companies_create'),
            'updatePath' => $this->generateUrl('crm_companies_update', ['id' => '__id__']),
            'deletePath' => $this->generateUrl('crm_companies_delete', ['id' => '__id__']),
            'listPath' => $this->generateUrl('crm_companies_list'),
        ]);
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        return $this->json($this->buildListPayload($pagination));
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = CompanyInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $company = $this->companyManager->create($input);

        return $this->json(['success' => true, 'company' => $this->companySerializer->serialize($company)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Company $company, Request $request): JsonResponse
    {
        $input = CompanyInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $this->companyManager->update($company, $input);

        return $this->json(['success' => true, 'company' => $this->companySerializer->serialize($company)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Company $company): JsonResponse
    {
        $this->companyManager->delete($company);

        return $this->json(['success' => true]);
    }

    private function buildListPayload(PaginationRequest $pagination): array
    {
        $result = $this->companyRepository->findPaginated($pagination->page, search: $pagination->search);

        return [
            'ok' => true,
            'items' => array_map($this->companySerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
