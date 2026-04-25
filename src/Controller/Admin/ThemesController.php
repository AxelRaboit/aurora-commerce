<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Trait\JsonValidationTrait;
use App\Entity\Theme;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Manager\ThemeManager;
use App\Repository\ThemeRepository;
use App\Serializer\ThemeSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/themes', name: 'admin_themes')]
#[IsGranted(UserRoleEnum::Admin->value)]
final class ThemesController extends AbstractController
{
    use JsonValidationTrait;

    public function __construct(
        private readonly ThemeRepository $themeRepository,
        private readonly ThemeManager $themeManager,
        private readonly ThemeSerializer $themeSerializer,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        $themes = $this->themeRepository->findAll();
        $serialized = array_map($this->themeSerializer->serialize(...), $themes);

        return $this->render('admin/themes/index.html.twig', [
            'themes' => $serialized,
        ]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $slug = mb_strtolower(trim((string) ($data['slug'] ?? '')));
        $name = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? '')) ?: null;

        if ('' === $slug || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            return $this->json(['ok' => false, 'errors' => ['slug' => 'admin.themes.errors.slug_invalid']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ('' === $name) {
            return $this->json(['ok' => false, 'errors' => ['name' => 'admin.themes.errors.name_required']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (null !== $this->themeRepository->findBySlug($slug)) {
            return $this->json(['ok' => false, 'errors' => ['slug' => 'admin.themes.errors.slug_taken']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $theme = $this->themeManager->create($slug, $name, $description);

        return $this->json(['ok' => true, 'theme' => $this->themeSerializer->serialize($theme)]);
    }

    #[Route('/{id}/activate', name: '_activate', methods: [HttpMethodEnum::Post->value])]
    public function activate(Theme $theme): JsonResponse
    {
        $this->themeManager->activate($theme);

        return $this->json(['ok' => true]);
    }

    #[Route('/{id}', name: '_update', methods: [HttpMethodEnum::Put->value])]
    public function update(Theme $theme, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $name = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? '')) ?: null;
        $config = isset($data['config']) && is_array($data['config']) ? $data['config'] : [];

        if ('' === $name) {
            return $this->json(['ok' => false, 'errors' => ['name' => 'admin.themes.errors.name_required']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->themeManager->update($theme, $name, $description, $config);

        return $this->json(['ok' => true, 'theme' => $this->themeSerializer->serialize($theme)]);
    }

    #[Route('/{id}', name: '_delete', methods: [HttpMethodEnum::Delete->value])]
    public function delete(Theme $theme): JsonResponse
    {
        if ('default' === $theme->getSlug()) {
            return $this->json(['ok' => false, 'error' => 'admin.themes.cannotDeleteDefault'], Response::HTTP_BAD_REQUEST);
        }

        if ($theme->isActive()) {
            return $this->json(['ok' => false, 'error' => 'admin.themes.cannotDeleteActive'], Response::HTTP_BAD_REQUEST);
        }

        $this->themeManager->delete($theme);

        return $this->json(['ok' => true]);
    }

}
