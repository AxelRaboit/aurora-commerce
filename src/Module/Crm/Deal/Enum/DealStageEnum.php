<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Enum;

enum DealStageEnum: string
{
    case Lead = 'lead';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';
}
