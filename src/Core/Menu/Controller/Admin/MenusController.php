<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Menu\DTO\MenuItemPayload;
use Aurora\Core\Menu\Entity\Menu;
use Aurora\Core\Menu\Entity\MenuItem;
use Aurora\Core\Menu\Manager\MenuManager;
use Aurora\Core\Menu\Repository\MenuRepository;
use Aurora\Core\Menu\Serializer\MenuSerializer;
use Aurora\Core\Menu\Service\MenuPickerService;
use Aurora\Core\Menu\View\MenusViewBuilder;
use Aurora\Core\Validation\Service\PayloadValidator;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/menus', name: 'admin_menus')]
#[IsGranted('core.menus.manage')]
class MenusController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly MenuManager $menuManager,
        private readonly MenuRepository $menuRepository,
        private readonly MenuSerializer $menuSerializer,
        private readonly MenuPickerService $menuPickerService,
        private readonly PayloadValidator $payloadValidator,
        private readonly MenusViewBuilder $viewBuilder,
    ) {}

    // ── Page (Vue SPA) ────────────────────────────────────────────────────────

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        /** @var list<string> $locales */
        $locales = $this->getParameter('kernel.enabled_locales');

        return $this->render('@Core/admin/menus/index.html.twig', $this->viewBuilder->indexView($locales));
    }

    // ── Menus CRUD ────────────────────────────────────────────────────────────

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(): JsonResponse
    {
        $menus = array_map(
            $this->menuSerializer->serialize(...),
            $this->menuRepository->findAll(),
        );

        return $this->jsonSuccess(['menus' => $menus]);
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function createMenu(): JsonResponse
    {
        // Menu creation is reserved to the aurora:menus:sync command — admins
        // only manage the items of system menus (primary, footer, …).
        return $this->jsonForbidden();
    }

    #[Route('/{id}', name: '_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function show(Menu $menu): JsonResponse
    {
        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($menu)]);
    }

    #[Route('/{id}/update', name: '_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function updateMenu(Menu $menu, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);

        try {
            $this->menuManager->updateMenu(
                $menu,
                (string) ($data['name'] ?? ''),
                (string) ($data['location'] ?? ''),
                isset($data['description']) ? (string) $data['description'] : null,
            );
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($menu)]);
    }

    #[Route('/{id}/delete', name: '_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function deleteMenu(Menu $menu): JsonResponse
    {
        try {
            $this->menuManager->deleteMenu($menu);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess();
    }

    // ── Items CRUD ────────────────────────────────────────────────────────────

    #[Route('/{id}/items/create', name: '_items_create', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function createItem(Menu $menu, Request $request): JsonResponse
    {
        $payload = MenuItemPayload::fromArray($this->decodeJson($request));
        if (null !== $error = $this->payloadValidator->firstError($payload)) {
            return $this->jsonFailure($error);
        }

        try {
            $this->menuManager->createItem($menu, $payload->targetType, $payload->targetId, $payload->toOptions());
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($menu)]);
    }

    #[Route('/items/{id}/update', name: '_items_update', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function updateItem(MenuItem $item, Request $request): JsonResponse
    {
        $payload = MenuItemPayload::fromArray($this->decodeJson($request));
        if (null !== $error = $this->payloadValidator->firstError($payload)) {
            return $this->jsonFailure($error);
        }

        try {
            $this->menuManager->updateItem($item, $payload->targetType, $payload->targetId, $payload->toOptions());
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        foreach ($payload->translations as $locale => $label) {
            $this->menuManager->setTranslation($item, $locale, $label);
        }

        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($item->getMenu())]);
    }

    #[Route('/items/{id}/delete', name: '_items_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function deleteItem(MenuItem $item): JsonResponse
    {
        $menu = $item->getMenu();
        $this->menuManager->deleteItem($item);

        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($menu)]);
    }

    #[Route('/{id}/items/reorder', name: '_items_reorder', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    public function reorderItems(Menu $menu, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $payload = is_array($data['items'] ?? null) ? $data['items'] : [];

        try {
            $this->menuManager->reorderItems($menu, $payload);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonFailure($invalidArgumentException->getMessage());
        }

        return $this->jsonSuccess(['menu' => $this->menuSerializer->serializeFull($menu)]);
    }

    // ── Pickers (autocomplete) ────────────────────────────────────────────────

    #[Route('/picker/posts', name: '_picker_posts', methods: [HttpMethodEnum::Get->value])]
    public function pickerPosts(Request $request): JsonResponse
    {
        return $this->jsonSuccess(['items' => $this->menuPickerService->posts(
            mb_trim((string) $request->query->get('q', '')),
            $request->query->getInt('postTypeId') ?: null,
        )]);
    }

    #[Route('/picker/terms', name: '_picker_terms', methods: [HttpMethodEnum::Get->value])]
    public function pickerTerms(Request $request): JsonResponse
    {
        return $this->jsonSuccess(['items' => $this->menuPickerService->terms(
            mb_trim((string) $request->query->get('q', '')),
            $request->query->getInt('taxonomyId') ?: null,
        )]);
    }

    #[Route('/picker/post-types', name: '_picker_post_types', methods: [HttpMethodEnum::Get->value])]
    public function pickerPostTypes(Request $request): JsonResponse
    {
        return $this->jsonSuccess(['items' => $this->menuPickerService->postTypes(
            $request->query->getBoolean('withArchive'),
        )]);
    }

    #[Route('/picker/taxonomies', name: '_picker_taxonomies', methods: [HttpMethodEnum::Get->value])]
    public function pickerTaxonomies(): JsonResponse
    {
        return $this->jsonSuccess(['items' => $this->menuPickerService->taxonomies()]);
    }
}
