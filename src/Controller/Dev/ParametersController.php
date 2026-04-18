<?php

declare(strict_types=1);

namespace App\Controller\Dev;

use App\Enum\ApplicationParameter\VeloxApplicationParameterEnum;
use App\Enum\HttpMethodEnum;
use App\Enum\UserRoleEnum;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/parameters', name: 'dev_parameters')]
#[IsGranted(UserRoleEnum::Dev->value)]
final class ParametersController extends AbstractController
{
    public function __construct(private readonly SettingRepository $settingRepository) {}

    #[Route('', name: '')]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $result = $this->settingRepository->findPaginated($page);

        $labelsByKey = [];
        foreach (VeloxApplicationParameterEnum::cases() as $case) {
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

        return $this->render('admin/administration/index.html.twig', [
            'tab' => 'parameters',
            'parameters' => [
                'items' => $items,
                'total' => $result['total'],
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
            ],
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
