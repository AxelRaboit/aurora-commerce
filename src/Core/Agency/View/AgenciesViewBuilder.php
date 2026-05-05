<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\View;

use Aurora\Core\Agency\Repository\AgencyRepository;
use Aurora\Core\Agency\Serializer\AgencySerializer;

final readonly class AgenciesViewBuilder
{
    public function __construct(
        private AgencyRepository $agencyRepository,
        private AgencySerializer $agencySerializer,
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
