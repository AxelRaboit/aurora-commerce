<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Enum\SettingErrorCodeEnum;
use Aurora\Core\Setting\Exception\CascadeViolationException;
use Aurora\Core\Setting\Service\SettingsManager;
use Aurora\Core\Setting\View\SettingsViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/settings', name: 'backend_settings')]
#[IsGranted('core.settings.manage')]
final class SettingsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly SettingsViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Core/backend/settings/index.html.twig', $this->viewBuilder->indexView());
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

        $parameter = ApplicationParameterEnum::tryFrom($key);

        if (null === $parameter || !$parameter->isAdminAccessible()) {
            return $this->jsonForbidden();
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
            'mediaUrl' => 'media' === $parameter->getType() ? $this->viewBuilder->resolveMediaUrl($value) : null,
        ]);
    }
}
