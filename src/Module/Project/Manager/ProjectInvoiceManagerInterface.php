<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;
use Aurora\Module\Project\Entity\ProjectInterface;

interface ProjectInvoiceManagerInterface
{
    public function generate(ProjectInterface $project): InvoiceInterface;
}
