<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Controller\Dev;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
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
    public function __construct(private readonly SettingRepository $settingRepository) {}

    #[Route('', name: '')]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $result = $this->settingRepository->findPaginated($pagination->page, search: $pagination->search);

        $labelsByKey = [];
        foreach (ApplicationParameterEnum::cases() as $case) {
            $labelsByKey[$case->getKey()] = $case->getLabel();
        }

        $items = array_map(
            fn ($parameter): array => [
                'key' => $parameter->getKey(),
                'label' => $labelsByKey[$parameter->getKey()] ?? $parameter->getKey(),
                'value' => $parameter->getValue(),
                'description' => $parameter->getDescription(),
                'type' => $parameter->getType(),
                'group' => $parameter->getGroup(),
            ],
            $result['items'],
        );

        $payload = ['ok' => true, 'items' => $items, 'total' => $result['total'], 'page' => $result['page'], 'totalPages' => $result['totalPages']];

        if ('XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return $this->json($payload);
        }

        return $this->render('@Core/admin/administration/index.html.twig', [
            'tab' => 'parameters',
            'parameters' => $payload,
            'search' => $pagination->search ?? '',
        ]);
    }

    #[Route('/{key}', name: '_update', methods: [HttpMethodEnum::Patch->value])]
    public function update(string $key, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $value = isset($data['value']) ? (string) $data['value'] : null;

        $this->settingRepository->set($key, $value);

        return $this->json(['key' => $key, 'value' => $value]);
    }
}
