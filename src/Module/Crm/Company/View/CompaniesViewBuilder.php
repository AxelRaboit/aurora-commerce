<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Crm\Company\Repository\CompanyRepository;
use Aurora\Module\Crm\Company\Serializer\CompanySerializer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin companies list view. Centralises URL
 * generation + serialisation so the controller stays focused on HTTP flow.
 */
final readonly class CompaniesViewBuilder
{
    public function __construct(
        private CompanyRepository $companyRepository,
        private CompanySerializer $companySerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(PaginationRequest $pagination): array
    {
        return [
            'companies' => $this->buildListPayload($pagination),
            'search' => $pagination->search ?? '',
            'createPath' => $this->urlGenerator->generate('backend_crm_companies_create'),
            'updatePath' => $this->urlGenerator->generate('backend_crm_companies_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_crm_companies_delete', ['id' => '__id__']),
            'listPath' => $this->urlGenerator->generate('backend_crm_companies_list'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildListPayload(PaginationRequest $pagination): array
    {
        $result = $this->companyRepository->findPaginated($pagination->page, search: $pagination->search);

        return [
            'success' => true,
            'items' => array_map($this->companySerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
