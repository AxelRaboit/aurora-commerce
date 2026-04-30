<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\View;

use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Company\Serializer\CompanySerializer;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Crm\Contact\Serializer\ContactSerializer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin company detail view. Centralises
 * URL generation + serialisation for the show screen.
 */
final readonly class CompanyDetailViewBuilder
{
    public function __construct(
        private CompanySerializer $companySerializer,
        private ContactRepository $contactRepository,
        private ContactSerializer $contactSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function showView(Company $company): array
    {
        $contactResult = $this->contactRepository->findPaginated(1, 50, companyId: $company->getId());
        $contacts = array_map($this->contactSerializer->serialize(...), $contactResult['items']);

        return [
            'company' => $this->companySerializer->serialize($company),
            'contacts' => $contacts,
            'backPath' => $this->urlGenerator->generate('crm_companies'),
            'updatePath' => $this->urlGenerator->generate('crm_companies_update', ['id' => $company->getId()]),
            'deletePath' => $this->urlGenerator->generate('crm_companies_delete', ['id' => $company->getId()]),
            'createContactPath' => $this->urlGenerator->generate('crm_contacts_create'),
            'contactsListPath' => $this->urlGenerator->generate('crm_contacts_list'),
        ];
    }
}
