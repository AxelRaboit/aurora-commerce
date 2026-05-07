<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Company\View\CompanyDetailViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/crm/companies/{id}', name: 'backend_crm_companies_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
#[IsGranted('crm.companies.manage')]
final class CompanyDetailController extends AbstractController
{
    public function __construct(
        private readonly CompanyDetailViewBuilder $viewBuilder,
    ) {}

    public function __invoke(Company $company): Response
    {
        return $this->render('@Crm/backend/companies/show.html.twig', $this->viewBuilder->showView($company));
    }
}
