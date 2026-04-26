<?php

declare(strict_types=1);

namespace App\Core\Setting\Controller\Admin;

use App\Core\Enum\HttpMethodEnum;
use App\Core\Setting\Enum\ApplicationParameterEnum;
use App\Core\Setting\Repository\SettingRepository;
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
    public function __construct(private readonly SettingRepository $settingRepository) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        $groups = [];

        foreach (ApplicationParameterEnum::cases() as $parameter) {
            if (!$parameter->isAdminAccessible()) {
                continue;
            }

            $groupName = $parameter->getGroup();

            $groups[$groupName][] = [
                'key' => $parameter->getKey(),
                'label' => $parameter->getLabel(),
                'description' => $parameter->getDescription(),
                'type' => $parameter->getType(),
                'group' => $groupName,
                'value' => $this->settingRepository->get($parameter->getKey(), $parameter->getDefaultValue()),
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

        $this->settingRepository->set($key, $value);

        return $this->json(['ok' => true, 'key' => $key, 'value' => $value]);
    }
}
