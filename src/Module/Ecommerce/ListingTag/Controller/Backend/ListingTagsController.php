<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Ecommerce\ListingTag\Dto\ListingTagInputFactoryInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Ecommerce\ListingTag\Manager\ListingTagManagerInterface;
use Aurora\Module\Ecommerce\ListingTag\Repository\ListingTagRepository;
use Aurora\Module\Ecommerce\ListingTag\Serializer\ListingTagSerializerInterface;
use Aurora\Module\Ecommerce\ListingTag\View\ListingTagsViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/ecommerce/listing-tags', name: 'backend_ecommerce_listing_tags')]
#[IsGranted('ecommerce.listings.view')]
final class ListingTagsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly ListingTagRepository $tagRepository,
        private readonly ListingTagSerializerInterface $serializer,
        private readonly ListingTagManagerInterface $manager,
        private readonly ListingTagInputFactoryInterface $inputFactory,
        private readonly PayloadValidator $payloadValidator,
        private readonly ListingTagsViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render(
            '@Ecommerce/backend/listing-tags/index.html.twig',
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
    #[IsGranted('ecommerce.listings.create')]
    public function create(Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray(json_decode($request->getContent(), true) ?? []);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $tag = $this->manager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['_global' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['tag' => $this->serializer->serialize($tag)]);
    }

    #[Route('/{id}/update', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ecommerce.listings.edit')]
    public function update(ListingTagInterface $tag, Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray(json_decode($request->getContent(), true) ?? []);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->manager->update($tag, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['_global' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['tag' => $this->serializer->serialize($tag)]);
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ecommerce.listings.delete')]
    public function delete(ListingTagInterface $tag): JsonResponse
    {
        $this->manager->delete($tag);

        return $this->jsonSuccess();
    }

    /** @return list<array<string, mixed>> */
    private function buildListPayload(): array
    {
        $tags = $this->tagRepository->findAllOrdered();

        return array_map(fn ($tag): array => $this->serializer->serialize($tag), $tags);
    }
}
