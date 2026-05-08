<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CompanyInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'crm.companies.errors.name_required')]
        #[Assert\Length(max: 150)]
        public string $name = '',
        #[Assert\Length(max: 100)]
        public ?string $industry = null,
        #[Assert\Url(message: 'crm.companies.errors.website_invalid')]
        #[Assert\Length(max: 255)]
        public ?string $website = null,
        #[Assert\Length(max: 50)]
        public ?string $phone = null,
        #[Assert\Length(max: 255)]
        public ?string $address = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: Str::trimFromArray($data, 'name'),
            industry: Str::trimOrNullFromArray($data, 'industry'),
            website: Str::trimOrNullFromArray($data, 'website'),
            phone: Str::trimOrNullFromArray($data, 'phone'),
            address: Str::trimOrNullFromArray($data, 'address'),
            notes: Str::trimOrNullFromArray($data, 'notes'),
        );
    }
}
