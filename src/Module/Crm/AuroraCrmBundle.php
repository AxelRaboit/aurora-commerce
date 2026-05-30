<?php

declare(strict_types=1);

namespace Aurora\Module\Crm;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\ContactTag\Entity\ContactTag;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Entity\DealInterface;

/** Self-contained bundle for the Crm module. @see AbstractAuroraModuleBundle */
final class AuroraCrmBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'Crm';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            CompanyInterface::class => Company::class,
            ContactInterface::class => Contact::class,
            ContactTagInterface::class => ContactTag::class,
            DealInterface::class => Deal::class,
        ];
    }
}
