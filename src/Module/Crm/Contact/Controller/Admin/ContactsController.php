<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Crm\Contact\Contract\ContactManagerInterface;
use Aurora\Module\Crm\Contact\DTO\ContactInput;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Serializer\ContactSerializer;
use Aurora\Module\Crm\Contact\View\ContactsViewBuilder;
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
        private readonly ContactSerializer $contactSerializer,
        private readonly ContactManagerInterface $contactManager,
        private readonly PayloadValidator $payloadValidator,
        private readonly ContactsViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->render('@Crm/admin/contacts/index.html.twig', $this->viewBuilder->indexView($pagination));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        return $this->json($this->viewBuilder->buildListPayload($pagination));
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = ContactInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $contact = $this->contactManager->create($input);

        return $this->jsonSuccess(['contact' => $this->contactSerializer->serialize($contact)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Contact $contact, Request $request): JsonResponse
    {
        $input = ContactInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        $this->contactManager->update($contact, $input);

        return $this->jsonSuccess(['contact' => $this->contactSerializer->serialize($contact)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('crm.contacts.delete')]
    public function delete(Contact $contact): JsonResponse
    {
        $this->contactManager->delete($contact);

        return $this->jsonSuccess();
    }
}
