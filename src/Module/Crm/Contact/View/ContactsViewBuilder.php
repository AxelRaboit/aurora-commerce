<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\View;

use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Crm\Contact\Serializer\ContactSerializer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin contacts list view. Centralises URL
 * generation + serialisation so the controller stays focused on HTTP flow.
 */
final readonly class ContactsViewBuilder
{
    public function __construct(
        private ContactRepository $contactRepository,
        private ContactSerializer $contactSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(PaginationRequest $pagination): array
    {
        return [
            'contacts' => $this->buildListPayload($pagination),
            'search' => $pagination->search ?? '',
            'createPath' => $this->urlGenerator->generate('crm_contacts_create'),
            'updatePath' => $this->urlGenerator->generate('crm_contacts_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('crm_contacts_delete', ['id' => '__id__']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildListPayload(PaginationRequest $pagination): array
    {
        $result = $this->contactRepository->findPaginated($pagination->page, search: $pagination->search);

        return [
            'success' => true,
            'items' => array_map($this->contactSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
