<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Manager;

use Aurora\Module\Crm\ContactTag\Dto\ContactTagInputInterface;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;

interface ContactTagManagerInterface
{
    public function create(ContactTagInputInterface $input): ContactTagInterface;

    public function update(ContactTagInterface $contactTag, ContactTagInputInterface $input): void;

    public function delete(ContactTagInterface $contactTag): void;
}
