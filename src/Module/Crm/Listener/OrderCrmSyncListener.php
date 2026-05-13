<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Listener;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\Contact\Enum\ContactSourceEnum;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Ecommerce\Order\Event\OrderCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Creates (or enriches) a CRM Contact when an order is created.
 *
 * Opt-in via `crm_sync_orders` application parameter — disable in pure B2C
 * setups where every buyer should not become a CRM contact.
 *
 * Match strategy: locate an existing contact by email; if found, enrich
 * missing fields only. Otherwise create one with `source = Order` and a
 * `client` tag.
 */
#[AsEventListener]
final readonly class OrderCrmSyncListener
{
    public function __construct(
        private ContactRepository $contactRepository,
        private EntityManagerInterface $entityManager,
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
    ) {}

    public function __invoke(OrderCreatedEvent $event): void
    {
        if ('1' !== (string) $this->settingRepository->get(ApplicationParameterEnum::CrmSyncOrders->value, '0')) {
            return;
        }

        $order = $event->getOrder();
        $email = $order->getEmail();
        if ('' === $email) {
            return;
        }

        [$firstName, $lastName] = $this->splitName($order->getName());

        $contact = $this->contactRepository->findOneBy(['email' => $email]);

        if (!$contact instanceof ContactInterface) {
            $contact = new Contact();
            $contact->setReference($this->sequenceGenerator->next(SequencePrefixEnum::Contact->value));
            $contact->setEmail($email);
            $contact->setFirstName($firstName);
            $contact->setLastName($lastName);
            $contact->setSource(ContactSourceEnum::Order);
            $contact->addTag('client');

            $this->entityManager->persist($contact);
            $this->entityManager->flush();

            return;
        }

        $dirty = false;
        if ('' === $contact->getFirstName() && '' !== $firstName) {
            $contact->setFirstName($firstName);
            $dirty = true;
        }

        if ('' === $contact->getLastName() && '' !== $lastName) {
            $contact->setLastName($lastName);
            $dirty = true;
        }

        if (!in_array('client', $contact->getTags(), true)) {
            $contact->addTag('client');
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    /** @return array{string, string} */
    private function splitName(string $fullName): array
    {
        $trimmed = mb_trim($fullName);
        if ('' === $trimmed) {
            return ['', ''];
        }

        $parts = explode(' ', $trimmed, 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
