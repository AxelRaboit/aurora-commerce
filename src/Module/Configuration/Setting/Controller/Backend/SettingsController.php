<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Setting\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Module\Configuration\Setting\Configuration\SettingDefinitionRegistry;
use Aurora\Module\Configuration\Setting\Configuration\SettingFieldDescriptor;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Configuration\Setting\Enum\SettingErrorCodeEnum;
use Aurora\Module\Configuration\Setting\Exception\CascadeViolationException;
use Aurora\Module\Configuration\Setting\Service\SettingsService;
use Aurora\Module\Configuration\Setting\View\SettingsViewBuilder;
use Aurora\Module\Platform\User\Enum\UserRoleEnum;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const JSON_THROW_ON_ERROR;

#[Route('/backend/configuration/settings', name: 'backend_configuration_settings')]
#[IsGranted('configuration.settings.manage')]
final class SettingsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly SettingsService $settingsManager,
        private readonly SettingsViewBuilder $viewBuilder,
        private readonly SettingDefinitionRegistry $definitionRegistry,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Configuration/backend/settings/index.html.twig', $this->viewBuilder->indexView(
            isDev: $this->isGranted(UserRoleEnum::Dev->value),
        ));
    }

    #[Route('/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $key = isset($data['key']) ? (string) $data['key'] : null;
        $value = isset($data['value']) ? (string) $data['value'] : null;

        if (null === $key) {
            return $this->jsonFailure('Missing key');
        }

        $field = $this->definitionRegistry->getField($key);

        if (!$field instanceof SettingFieldDescriptor) {
            return $this->jsonForbidden();
        }

        if ($this->definitionRegistry->isDevOnly($key) && !$this->isGranted(UserRoleEnum::Dev->value)) {
            return $this->jsonForbidden();
        }

        if (ApplicationParameterEnum::ColorPickerPresets->value === $key) {
            $normalised = $this->normaliseColorPickerPresets($value);
            if (null === $normalised) {
                return $this->jsonFailure('invalid_color_presets');
            }

            $value = $normalised;
        }

        try {
            $this->settingsManager->set($key, $value);
        } catch (CascadeViolationException $cascadeViolationException) {
            return $this->jsonFailure(
                SettingErrorCodeEnum::CascadeViolation->value,
                HttpStatusEnum::Conflict->value,
                ['parentKey' => $cascadeViolationException->parentKey],
            );
        }

        return $this->jsonSuccess([
            'key' => $key,
            'value' => $value,
            'mediaUrl' => 'media' === $field->type ? $this->viewBuilder->resolveMediaUrl($value) : null,
        ]);
    }

    /**
     * Validates and normalises the color picker presets payload.
     * Accepts a JSON-encoded list of strings (or a raw list passed by mistake);
     * keeps only `#rrggbb` strings (lowercased) and re-encodes as compact JSON.
     * Returns null when the input is malformed or empty.
     */
    private function normaliseColorPickerPresets(?string $raw): ?string
    {
        if (null === $raw || '' === $raw) {
            return null;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($decoded) || [] === $decoded) {
            return null;
        }

        $valid = [];
        foreach ($decoded as $hex) {
            if (!is_string($hex) || 1 !== preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
                return null;
            }

            $valid[] = mb_strtolower($hex);
        }

        return json_encode($valid, JSON_THROW_ON_ERROR);
    }
}
