<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Enum;

enum ContactSourceEnum: string
{
    case Manual = 'manual';
    case Form = 'form';
    case Order = 'order';

    public function getLabel(): string
    {
        return 'backend.crm.contacts.sources.'.$this->value;
    }
}
