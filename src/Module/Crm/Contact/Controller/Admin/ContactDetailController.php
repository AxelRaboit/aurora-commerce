<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\View\ContactDetailViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/crm/contacts/{id}', name: 'crm_contacts_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
#[IsGranted('crm.contacts.view')]
final class ContactDetailController extends AbstractController
{
    public function __construct(
        private readonly ContactDetailViewBuilder $viewBuilder,
    ) {}

    public function __invoke(Contact $contact): Response
    {
        return $this->render('@Crm/admin/contacts/show.html.twig', $this->viewBuilder->showView($contact));
    }
}
