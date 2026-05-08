<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CompanyInput implements CompanyInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'crm.companies.errors.name_required')]
        #[Assert\Length(max: 150)]
        public readonly string $name = '',
        #[Assert\Length(max: 100)]
        public readonly ?string $industry = null,
        #[Assert\Url(message: 'crm.companies.errors.website_invalid')]
        #[Assert\Length(max: 255)]
        public readonly ?string $website = null,
        #[Assert\Length(max: 50)]
        public readonly ?string $phone = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $address = null,
        public readonly ?string $notes = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
}
