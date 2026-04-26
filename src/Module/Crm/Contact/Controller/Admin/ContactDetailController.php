<?php

declare(strict_types=1);

namespace App\Module\Crm\Contact\Controller\Admin;

use App\Core\Audit\Repository\AuditLogRepository;
use App\Core\Audit\Serializer\AuditLogSerializer;
use App\Core\Enum\HttpMethodEnum;
use App\Module\Crm\Contact\Entity\Contact;
use App\Module\Crm\Contact\Serializer\ContactSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/crm/contacts/{id}', name: 'crm_contacts_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
#[IsGranted('crm.contacts.view')]
final class ContactDetailController extends AbstractController
{
    public function __construct(
        private readonly ContactSerializer $contactSerializer,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly AuditLogSerializer $auditLogSerializer,
    ) {}

    public function __invoke(Contact $contact): Response
    {
        $activityResult = $this->auditLogRepository->findPaginatedForEntity('Contact', $contact->getId(), 1, 20);
        $activity = array_map($this->auditLogSerializer->serialize(...), $activityResult['items']);

        return $this->render('@Crm/admin/contacts/show.html.twig', [
            'contact' => $this->contactSerializer->serialize($contact),
            'activity' => $activity,
            'editPath' => $this->generateUrl('crm_contacts', []),
            'backPath' => $this->generateUrl('crm_contacts'),
            'updatePath' => $this->generateUrl('crm_contacts_update', ['id' => $contact->getId()]),
            'deletePath' => $this->generateUrl('crm_contacts_delete', ['id' => $contact->getId()]),
        ]);
    }
}
