<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Manager;

use Aurora\Module\Crm\Contact\Dto\ContactInputInterface;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;

interface ContactManagerInterface
{
    public function create(ContactInputInterface $input): ContactInterface;

    public function update(ContactInterface $contact, ContactInputInterface $input): void;

    public function delete(ContactInterface $contact): void;
}
