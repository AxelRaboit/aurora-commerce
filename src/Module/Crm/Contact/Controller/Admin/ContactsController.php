<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Crm\Contact\Contract\ContactManagerInterface;
use Aurora\Module\Crm\Contact\DTO\ContactInput;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Crm\Contact\Serializer\ContactSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/crm/contacts', name: 'crm_contacts')]
#[IsGranted('crm.contacts.view')]
final class ContactsController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly ContactRepository $contactRepository,
        private readonly ContactSerializer $contactSerializer,
        private readonly ContactManagerInterface $contactManager,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->render('@Crm/admin/contacts/index.html.twig', [
            'contacts' => $this->buildListPayload($pagination),
            'search' => $pagination->search ?? '',
            'createPath' => $this->generateUrl('crm_contacts_create'),
            'updatePath' => $this->generateUrl('crm_contacts_update', ['id' => '__id__']),
            'deletePath' => $this->generateUrl('crm_contacts_delete', ['id' => '__id__']),
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
        $input = ContactInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $contact = $this->contactManager->create($input);

        return $this->json(['success' => true, 'contact' => $this->contactSerializer->serialize($contact)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Contact $contact, Request $request): JsonResponse
    {
        $input = ContactInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $this->contactManager->update($contact, $input);

        return $this->json(['success' => true, 'contact' => $this->contactSerializer->serialize($contact)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('crm.contacts.delete')]
    public function delete(Contact $contact): JsonResponse
    {
        $this->contactManager->delete($contact);

        return $this->json(['success' => true]);
    }

    private function buildListPayload(PaginationRequest $pagination): array
    {
        $result = $this->contactRepository->findPaginated($pagination->page, search: $pagination->search);

        return [
            'ok' => true,
            'items' => array_map($this->contactSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
