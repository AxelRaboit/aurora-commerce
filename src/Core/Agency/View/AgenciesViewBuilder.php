<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\View;

use Aurora\Core\Agency\Repository\AgencyRepository;
use Aurora\Core\Agency\Serializer\AgencySerializerInterface;

class AgenciesViewBuilder
{
    public function __construct(
        protected readonly AgencyRepository $agencyRepository,
        protected readonly AgencySerializerInterface $agencySerializer,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(): array
    {
        return [
            'agencies' => array_map(
                $this->agencySerializer->serialize(...),
                $this->agencyRepository->findAllAlphabetical(),
            ),
        ];
    }
}
