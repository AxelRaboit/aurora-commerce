<?php

declare(strict_types=1);

namespace Aurora\Core\Service\View;

use Aurora\Core\Service\Repository\ServiceRepository;
use Aurora\Core\Service\Serializer\ServiceSerializer;

final readonly class ServicesViewBuilder
{
    public function __construct(
        private ServiceRepository $serviceRepository,
        private ServiceSerializer $serviceSerializer,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(): array
    {
        return [
            'services' => array_map(
                $this->serviceSerializer->serialize(...),
                $this->serviceRepository->findAllAlphabetical(),
            ),
        ];
    }
}
