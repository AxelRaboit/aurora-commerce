<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ContactTagInput implements ContactTagInputInterface
{
    public function __construct(
        public readonly string $label = '',
        public readonly ?string $slug = null,
        public readonly string $color = '#6366F1',
    ) {}

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ('' === mb_trim($this->label)) {
            $context->buildViolation('backend.crm.contact_tags.errors.label_required')
                ->atPath('label')
                ->addViolation();
        }

        if (1 !== preg_match('/^#[0-9a-fA-F]{6}$/', $this->color)) {
            $context->buildViolation('backend.crm.contact_tags.errors.color_invalid')
                ->atPath('color')
                ->addViolation();
        }
    }
}
