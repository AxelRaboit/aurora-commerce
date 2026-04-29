<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Enum\SettingErrorCodeEnum;
use Aurora\Core\Setting\Exception\CascadeViolationException;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\Setting\Service\SettingsManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/settings', name: 'admin_settings')]
#[IsGranted('core.settings.manage')]
final class SettingsController extends AbstractController
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly SettingsManager $settingsManager,
        private readonly MediaRepository $mediaRepository,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        $groups = [];

        foreach (ApplicationParameterEnum::cases() as $parameter) {
            if (!$parameter->isAdminAccessible()) {
                continue;
            }

            $groupName = $parameter->getGroup();

            $value = $this->settingRepository->get($parameter->getKey(), $parameter->getDefaultValue());

            $groups[$groupName][] = [
                'key' => $parameter->getKey(),
                'label' => $parameter->getLabel(),
                'description' => $parameter->getDescription(),
                'type' => $parameter->getType(),
                'group' => $groupName,
                'value' => $value,
                'requires' => $parameter->getCascadeRequires(),
                'mediaUrl' => 'media' === $parameter->getType() ? $this->resolveMediaUrl($value) : null,
            ];
        }

        return $this->render('@Core/admin/settings/index.html.twig', [
            'groups' => $groups,
            'mediaPickerPath' => $this->generateUrl('admin_media'),
            'postSearchPath' => $this->generateUrl('admin_posts_search'),
        ]);
    }

    #[Route('/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $key = isset($data['key']) ? (string) $data['key'] : null;
        $value = isset($data['value']) ? (string) $data['value'] : null;

        if (null === $key) {
            return $this->json(['ok' => false, 'error' => 'Missing key'], Response::HTTP_BAD_REQUEST);
        }

        $parameter = ApplicationParameterEnum::tryFrom($key);

        if (null === $parameter || !$parameter->isAdminAccessible()) {
            return $this->json(['ok' => false, 'error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->settingsManager->set($key, $value);
        } catch (CascadeViolationException $cascadeViolationException) {
            return $this->json([
                'ok' => false,
                'error' => SettingErrorCodeEnum::CascadeViolation->value,
                'parentKey' => $cascadeViolationException->parentKey,
            ], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'ok' => true,
            'key' => $key,
            'value' => $value,
            'mediaUrl' => 'media' === $parameter->getType() ? $this->resolveMediaUrl($value) : null,
        ]);
    }

    private function resolveMediaUrl(?string $rawId): ?string
    {
        if (null === $rawId || '' === $rawId) {
            return null;
        }

        $mediaId = (int) $rawId;
        if ($mediaId <= 0) {
            return null;
        }

        return $this->mediaRepository->find($mediaId)?->getPublicUrl();
    }
}
