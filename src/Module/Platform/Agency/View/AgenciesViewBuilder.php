<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Agency\View;

use Aurora\Module\Platform\Agency\Repository\AgencyRepository;
use Aurora\Module\Platform\Agency\Serializer\AgencySerializerInterface;

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
