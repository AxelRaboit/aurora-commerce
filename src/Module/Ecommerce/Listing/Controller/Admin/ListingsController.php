<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Ecommerce\Listing\Contract\ListingManagerInterface;
use Aurora\Module\Ecommerce\Listing\DTO\ListingInput;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\Listing\Serializer\ListingSerializer;
use Aurora\Module\Erp\Product\Repository\ProductRepository;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ecommerce/listings', name: 'ecommerce_listings')]
#[IsGranted('ecommerce.listings.view')]
final class ListingsController extends AbstractController
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly ProductRepository $productRepository,
        private readonly ListingSerializer $listingSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly ListingManagerInterface $listingManager,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        $payload = $this->buildListPayload($pagination);

        return $this->render('@Ecommerce/admin/listings/index.html.twig', [
            'listings' => $payload,
            'search' => $pagination->search ?? '',
            'createPath' => $this->generateUrl('ecommerce_listings_create'),
            'updatePath' => $this->generateUrl('ecommerce_listings_update', ['id' => '__id__']),
            'deletePath' => $this->generateUrl('ecommerce_listings_delete', ['id' => '__id__']),
            'showPath' => $this->generateUrl('ecommerce_listings_show', ['id' => '__id__']),
            'productsPath' => $this->generateUrl('ecommerce_listings_products'),
        ]);
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination): JsonResponse
    {
        return $this->json($this->buildListPayload($pagination));
    }

    #[Route('/products', name: '_products', methods: [HttpMethodEnum::Get->value])]
    public function availableProducts(Request $request): JsonResponse
    {
        $search = mb_trim((string) $request->query->get('q', '')) ?: null;
        $result = $this->productRepository->findPaginated(1, 50, $search);

        $items = array_map(static fn ($product): array => [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
        ], $result['items']);

        return $this->json(['ok' => true, 'items' => $items, 'total' => $result['total']]);
    }

    #[Route('/search', name: '_search', methods: [HttpMethodEnum::Get->value])]
    public function search(Request $request): JsonResponse
    {
        $idsParam = $request->query->get('ids');
        if (null !== $idsParam && '' !== $idsParam) {
            $ids = array_values(array_filter(array_map(intval(...), explode(',', $idsParam)), static fn (int $id): bool => $id > 0));
            if ([] === $ids) {
                return $this->json(['results' => []]);
            }

            $listings = $this->listingRepository->findBy(['id' => $ids]);
            $results = array_map(static fn ($listing): array => [
                'id' => $listing->getId(),
                'title' => $listing->getDisplayTitle(),
                'slug' => $listing->getSlug(),
            ], $listings);

            return $this->json(['results' => $results]);
        }

        $query = mb_trim((string) $request->query->get('q', ''));
        if ('' === $query) {
            return $this->json(['results' => []]);
        }

        $result = $this->listingRepository->findPaginated(1, 20, search: $query);
        $results = array_map(static fn ($listing): array => [
            'id' => $listing->getId(),
            'title' => $listing->getDisplayTitle(),
            'slug' => $listing->getSlug(),
        ], $result['items']);

        return $this->json(['results' => $results]);
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ecommerce.listings.create')]
    public function create(Request $request): JsonResponse
    {
        $input = ListingInput::fromArray(json_decode($request->getContent(), true) ?? []);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $listing = $this->listingManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['_global' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'listing' => $this->listingSerializer->serialize($listing)]);
    }

    #[Route('/{id}/update', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ecommerce.listings.edit')]
    public function update(Listing $listing, Request $request): JsonResponse
    {
        $input = ListingInput::fromArray(json_decode($request->getContent(), true) ?? []);

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $this->listingManager->update($listing, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['_global' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'listing' => $this->listingSerializer->serialize($listing)]);
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ecommerce.listings.delete')]
    public function delete(Listing $listing): JsonResponse
    {
        $this->listingManager->delete($listing);

        return $this->json(['success' => true]);
    }

    /** @return array{ok: bool, items: list<array<string, mixed>>, total: int, page: int, totalPages: int} */
    private function buildListPayload(PaginationRequest $pagination): array
    {
        $result = $this->listingRepository->findPaginated($pagination->page, search: $pagination->search);

        return [
            'ok' => true,
            'items' => array_map($this->listingSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
