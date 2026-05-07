<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Controller\Dev;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Setting\Enum\SettingErrorCodeEnum;
use Aurora\Core\Setting\Exception\CascadeViolationException;
use Aurora\Core\Setting\Service\SettingsManager;
use Aurora\Core\Setting\View\ParametersViewBuilder;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/dashboard/parameters', name: 'dev_parameters')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class ParametersController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly ParametersViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '')]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $payload = $this->viewBuilder->parametersPayload($pagination->page, $pagination->search);

        if ('XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return $this->json($payload);
        }

        return $this->render('@Core/backend/dev/index.html.twig', $this->viewBuilder->indexView($payload, $pagination->search));
    }

    #[Route('/{key}', name: '_update', methods: [HttpMethodEnum::Patch->value])]
    public function update(string $key, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $value = isset($data['value']) ? (string) $data['value'] : null;

        try {
            $this->settingsManager->set($key, $value);
        } catch (CascadeViolationException $cascadeViolationException) {
            return $this->jsonFailure(
                SettingErrorCodeEnum::CascadeViolation->value,
                Response::HTTP_CONFLICT,
                ['parentKey' => $cascadeViolationException->parentKey],
            );
        }

        return $this->json(['key' => $key, 'value' => $value]);
    }
}
