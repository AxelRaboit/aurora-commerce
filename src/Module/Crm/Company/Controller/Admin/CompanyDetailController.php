<?php

declare(strict_types=1);

namespace App\Module\Crm\Company\Controller\Admin;

use App\Core\Enum\HttpMethodEnum;
use App\Module\Crm\Company\Entity\Company;
use App\Module\Crm\Company\Serializer\CompanySerializer;
use App\Module\Crm\Contact\Repository\ContactRepository;
use App\Module\Crm\Contact\Serializer\ContactSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/crm/companies/{id}', name: 'crm_companies_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
#[IsGranted('crm.companies.manage')]
final class CompanyDetailController extends AbstractController
{
    public function __construct(
        private readonly CompanySerializer $companySerializer,
        private readonly ContactRepository $contactRepository,
        private readonly ContactSerializer $contactSerializer,
    ) {}

    public function __invoke(Company $company): Response
    {
        $contactResult = $this->contactRepository->findPaginated(1, 50, companyId: $company->getId());
        $contacts = array_map($this->contactSerializer->serialize(...), $contactResult['items']);

        return $this->render('@Crm/admin/companies/show.html.twig', [
            'company' => $this->companySerializer->serialize($company),
            'contacts' => $contacts,
            'backPath' => $this->generateUrl('crm_companies'),
            'updatePath' => $this->generateUrl('crm_companies_update', ['id' => $company->getId()]),
            'deletePath' => $this->generateUrl('crm_companies_delete', ['id' => $company->getId()]),
            'createContactPath' => $this->generateUrl('crm_contacts_create'),
            'contactsListPath' => $this->generateUrl('crm_contacts_list'),
        ]);
    }
}
