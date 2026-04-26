<?php

declare(strict_types=1);

namespace App\Module\Crm\Contact\Contract;

use App\Module\Crm\Contact\DTO\ContactInput;
use App\Module\Crm\Contact\Entity\Contact;

interface ContactManagerInterface
{
    public function create(ContactInput $input): Contact;

    public function update(Contact $contact, ContactInput $input): void;

    public function delete(Contact $contact): void;
}
