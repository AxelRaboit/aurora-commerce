<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Enum;

enum WeldingValidatorRoleEnum: string
{
    case Inspector = 'inspector';
    case QualityAssurance = 'quality_assurance';
    case Supervisor = 'supervisor';
    case Customer = 'customer';

    public function getLabelKey(): string
    {
        return 'backend.welding.validator_role_'.$this->value;
    }
}
