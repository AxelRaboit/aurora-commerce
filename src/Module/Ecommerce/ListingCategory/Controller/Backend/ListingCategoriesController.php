<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Ecommerce\ListingCategory\Dto\ListingCategoryInputFactoryInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategory;
use Aurora\Module\Ecommerce\ListingCategory\Manager\ListingCategoryManagerInterface;
use Aurora\Module\Ecommerce\ListingCategory\Repository\ListingCategoryRepository;
use Aurora\Module\Ecommerce\ListingCategory\Serializer\ListingCategorySerializerInterface;
use Aurora\Module\Ecommerce\ListingCategory\View\ListingCategoriesViewBuilder;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/ecommerce/listing-categories', name: 'backend_ecommerce_listing_categories')]
#[IsGranted('ecommerce.listings.view')]
final class ListingCategoriesController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly ListingCategoryRepository $categoryRepository,
        private readonly ListingCategorySerializerInterface $serializer,
        private readonly ListingCategoryManagerInterface $manager,
        private readonly ListingCategoryInputFactoryInterface $inputFactory,
        private readonly PayloadValidator $payloadValidator,
        private readonly ListingCategoriesViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render(
            '@Ecommerce/backend/listing_categories/index.html.twig',
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
            $category = $this->manager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['_global' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['category' => $this->serializer->serialize($category)]);
    }

    #[Route('/{id}/update', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ecommerce.listings.edit')]
    public function update(ListingCategory $category, Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray(json_decode($request->getContent(), true) ?? []);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->manager->update($category, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['_global' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['category' => $this->serializer->serialize($category)]);
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ecommerce.listings.delete')]
    public function delete(ListingCategory $category): JsonResponse
    {
        $this->manager->delete($category);

        return $this->jsonSuccess();
    }

    /** @return list<array<string, mixed>> */
    private function buildListPayload(): array
    {
        $categories = $this->categoryRepository->findAllOrdered();

        return array_map(fn ($category): array => $this->serializer->serialize($category), $categories);
    }
}
