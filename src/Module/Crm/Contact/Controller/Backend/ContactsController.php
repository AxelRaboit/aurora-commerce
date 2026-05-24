<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Crm\Contact\Dto\ContactInputFactoryInterface;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Manager\ContactManagerInterface;
use Aurora\Module\Crm\Contact\Serializer\ContactSerializerInterface;
use Aurora\Module\Crm\Contact\View\ContactsViewBuilder;
use Aurora\Module\Dev\Audit\Repository\AuditLogRepository;
use Aurora\Module\Dev\Audit\Serializer\AuditLogSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/crm/contacts', name: 'backend_crm_contacts')]
#[IsGranted('crm.contacts.view')]
final class ContactsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ContactSerializerInterface $contactSerializer,
        private readonly ContactManagerInterface $contactManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly ContactsViewBuilder $viewBuilder,
        private readonly ContactInputFactoryInterface $contactInputFactory,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly AuditLogSerializer $auditLogSerializer,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->render('@Crm/backend/contacts/index.html.twig', $this->viewBuilder->indexView($pagination));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        return $this->json($this->viewBuilder->buildListPayload($pagination));
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = $this->contactInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $contact = $this->contactManager->create($input);

        return $this->jsonSuccess(['contact' => $this->contactSerializer->serialize($contact)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Contact $contact, Request $request): JsonResponse
    {
        $input = $this->contactInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->contactManager->update($contact, $input);

        return $this->jsonSuccess(['contact' => $this->contactSerializer->serialize($contact)]);
    }

    #[Route('/{id}/activity', name: '_activity', methods: [HttpMethodEnum::Get->value])]
    public function activity(Contact $contact): JsonResponse
    {
        $result = $this->auditLogRepository->findPaginatedForEntity('Contact', $contact->getId(), 1, 20);

        return $this->jsonSuccess([
            'items' => array_map($this->auditLogSerializer->serialize(...), $result['items']),
        ]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('crm.contacts.delete')]
    public function delete(Contact $contact): JsonResponse
    {
        $this->contactManager->delete($contact);

        return $this->jsonSuccess();
    }
}
