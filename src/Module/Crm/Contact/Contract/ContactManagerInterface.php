<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Contract;

use Aurora\Module\Crm\Contact\DTO\ContactInput;
use Aurora\Module\Crm\Contact\Entity\Contact;

interface ContactManagerInterface
{
    public function create(ContactInput $input): Contact;

    public function update(Contact $contact, ContactInput $input): void;

    public function delete(Contact $contact): void;
}
