<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\View;

use Aurora\Core\Audit\Repository\AuditLogRepository;
use Aurora\Core\Audit\Serializer\AuditLogSerializer;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Serializer\ContactSerializer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin contact detail view. Centralises
 * URL generation + serialisation for the show screen.
 */
final readonly class ContactDetailViewBuilder
{
    public function __construct(
        private ContactSerializer $contactSerializer,
        private AuditLogRepository $auditLogRepository,
        private AuditLogSerializer $auditLogSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function showView(Contact $contact): array
    {
        $activityResult = $this->auditLogRepository->findPaginatedForEntity('Contact', $contact->getId(), 1, 20);
        $activity = array_map($this->auditLogSerializer->serialize(...), $activityResult['items']);

        return [
            'contact' => $this->contactSerializer->serialize($contact),
            'activity' => $activity,
            'editPath' => $this->urlGenerator->generate('crm_contacts', []),
            'backPath' => $this->urlGenerator->generate('crm_contacts'),
            'updatePath' => $this->urlGenerator->generate('crm_contacts_update', ['id' => $contact->getId()]),
            'deletePath' => $this->urlGenerator->generate('crm_contacts_delete', ['id' => $contact->getId()]),
        ];
    }
}
