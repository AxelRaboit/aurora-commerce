<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Controller\Dev;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Enum\SettingErrorCodeEnum;
use Aurora\Core\Setting\Exception\CascadeViolationException;
use Aurora\Core\Setting\Service\SettingsManager;
use Aurora\Core\Setting\View\ModulesViewBuilder;
use Aurora\Core\User\Enum\UserRoleEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/dashboard/modules', name: 'dev_modules')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class ModulesController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly ModulesViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $payload = $this->viewBuilder->modulesPayload();

        if ('XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return $this->json($payload);
        }

        return $this->render('@Core/admin/dev/index.html.twig', $this->viewBuilder->indexView($payload));
    }

    #[Route('/{key}', name: '_update', methods: [HttpMethodEnum::Patch->value])]
    public function update(string $key, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $value = isset($data['value']) ? (string) $data['value'] : null;

        $parameter = ApplicationParameterEnum::tryFrom($key);

        if (null === $parameter || $parameter->getGroup() !== 'modules') {
            return $this->jsonForbidden();
        }

        try {
            $this->settingsManager->set($key, $value);
        } catch (CascadeViolationException $cascadeViolationException) {
            return $this->jsonFailure(
                SettingErrorCodeEnum::CascadeViolation->value,
                Response::HTTP_CONFLICT,
                ['parentKey' => $cascadeViolationException->parentKey],
            );
        }

        return $this->jsonSuccess(['key' => $key, 'value' => $value]);
    }
}
