<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Trait\JsonValidationTrait;
use App\DTO\ThemeInput;
use App\Entity\Theme;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Manager\ThemeManager;
use App\Repository\ThemeRepository;
use App\Serializer\ThemeSerializer;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/themes', name: 'admin_themes')]
#[IsGranted(UserRoleEnum::Admin->value)]
final class ThemesController extends AbstractController
{
    use JsonValidationTrait;

    public function __construct(
        private readonly ThemeRepository $themeRepository,
        private readonly ThemeManager $themeManager,
        private readonly ThemeSerializer $themeSerializer,
        private readonly ValidatorInterface $validator,
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
        $input = ThemeInput::fromArray($this->decodeJson($request));
        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['ok' => false, 'errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $theme = $this->themeManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            [$field, $message] = explode('|', $invalidArgumentException->getMessage(), 2) + ['_error', ''];

            return $this->json(['ok' => false, 'errors' => [$field => $message]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['ok' => true, 'theme' => $this->themeSerializer->serialize($theme)]);
    }

    #[Route('/{id}/activate', name: '_activate', methods: [HttpMethodEnum::Post->value])]
    public function activate(Theme $theme): JsonResponse
    {
        $this->themeManager->activate($theme);

        return $this->json(['ok' => true]);
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Theme $theme, Request $request): JsonResponse
    {
        $input = ThemeInput::fromArray(array_merge($this->decodeJson($request), ['slug' => $theme->getSlug()]));
        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            return $this->json(['ok' => false, 'errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->themeManager->update($theme, $input);

        return $this->json(['ok' => true, 'theme' => $this->themeSerializer->serialize($theme)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Theme $theme): JsonResponse
    {
        try {
            $this->themeManager->delete($theme);
        } catch (RuntimeException $runtimeException) {
            return $this->json(['ok' => false, 'error' => $runtimeException->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['ok' => true]);
    }
}
