<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Crm\ContactTag\Dto\ContactTagInputFactoryInterface;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Aurora\Module\Crm\ContactTag\Manager\ContactTagManagerInterface;
use Aurora\Module\Crm\ContactTag\Repository\ContactTagRepository;
use Aurora\Module\Crm\ContactTag\Serializer\ContactTagSerializerInterface;
use Aurora\Module\Crm\ContactTag\View\ContactTagsViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/crm/contact-tags', name: 'backend_crm_contact_tags')]
#[IsGranted('crm.contacts.view')]
final class ContactTagsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly ContactTagRepository $contactTagRepository,
        private readonly ContactTagSerializerInterface $serializer,
        private readonly ContactTagManagerInterface $manager,
        private readonly ContactTagInputFactoryInterface $inputFactory,
        private readonly PayloadValidator $payloadValidator,
        private readonly ContactTagsViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render(
            '@Crm/backend/contact-tags/index.html.twig',
            $this->viewBuilder->indexView($this->buildListPayload()),
        );
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'items' => $this->buildListPayload(),
        ]);
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('crm.contacts.create')]
    public function create(Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray(json_decode($request->getContent(), true) ?? []);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $contactTag = $this->manager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['_global' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['tag' => $this->serializer->serialize($contactTag)]);
    }

    #[Route('/{id}/update', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('crm.contacts.edit')]
    public function update(ContactTagInterface $contactTag, Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray(json_decode($request->getContent(), true) ?? []);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->manager->update($contactTag, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['_global' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['tag' => $this->serializer->serialize($contactTag)]);
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('crm.contacts.delete')]
    public function delete(ContactTagInterface $contactTag): JsonResponse
    {
        $this->manager->delete($contactTag);

        return $this->jsonSuccess();
    }

    /** @return list<array<string, mixed>> */
    private function buildListPayload(): array
    {
        $contactTags = $this->contactTagRepository->findAllOrdered();

        return array_map($this->serializer->serialize(...), $contactTags);
    }
}
