<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ContactInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'crm.contacts.errors.first_name_required')]
        #[Assert\Length(max: 100)]
        public string $firstName = '',
        #[Assert\NotBlank(message: 'crm.contacts.errors.last_name_required')]
        #[Assert\Length(max: 100)]
        public string $lastName = '',
        #[Assert\Email(message: 'crm.contacts.errors.email_invalid')]
        #[Assert\Length(max: 180)]
        public ?string $email = null,
        #[Assert\Length(max: 50)]
        public ?string $phone = null,
        #[Assert\Positive]
        public ?int $companyId = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: Str::trimFromArray($data, 'firstName'),
            lastName: Str::trimFromArray($data, 'lastName'),
            email: Str::trimOrNullFromArray($data, 'email'),
            phone: Str::trimOrNullFromArray($data, 'phone'),
            companyId: isset($data['companyId']) && '' !== (string) $data['companyId'] ? (int) $data['companyId'] : null,
            notes: Str::trimOrNullFromArray($data, 'notes'),
        );
    }
}
