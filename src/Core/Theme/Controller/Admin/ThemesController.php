<?php

declare(strict_types=1);

namespace Aurora\Core\Theme\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Theme\DTO\ThemeInput;
use Aurora\Core\Theme\Entity\Theme;
use Aurora\Core\Theme\Manager\ThemeManager;
use Aurora\Core\Theme\Serializer\ThemeSerializer;
use Aurora\Core\Theme\View\ThemesViewBuilder;
use Aurora\Core\Validation\Service\PayloadValidator;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/themes', name: 'admin_themes')]
#[IsGranted('core.themes.manage')]
final class ThemesController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ThemeManager $themeManager,
        private readonly ThemeSerializer $themeSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly ThemesViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Core/admin/themes/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = ThemeInput::fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $theme = $this->themeManager->create($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            [$field, $message] = explode('|', $invalidArgumentException->getMessage(), 2) + ['_error', ''];

            return $this->jsonInvalidInput([$field => $message]);
        }

        return $this->jsonSuccess(['theme' => $this->themeSerializer->serialize($theme)]);
    }

    #[Route('/{id}/activate', name: '_activate', methods: [HttpMethodEnum::Post->value])]
    public function activate(Theme $theme): JsonResponse
    {
        $this->themeManager->activate($theme);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/edit', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Theme $theme, Request $request): JsonResponse
    {
        $input = ThemeInput::fromArray(array_merge($this->decodeJson($request), ['slug' => $theme->getSlug()]));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->themeManager->update($theme, $input);

        return $this->jsonSuccess(['theme' => $this->themeSerializer->serialize($theme)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Theme $theme): JsonResponse
    {
        try {
            $this->themeManager->delete($theme);
        } catch (RuntimeException $runtimeException) {
            return $this->jsonFailure($runtimeException->getMessage());
        }

        return $this->jsonSuccess();
    }
}
