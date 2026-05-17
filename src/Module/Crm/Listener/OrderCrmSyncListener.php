<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Listener;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\Contact\Enum\ContactSourceEnum;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Aurora\Module\Crm\ContactTag\Repository\ContactTagRepository;
use Aurora\Module\Crm\Setting\CrmSettingEnum;
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
    private const string CLIENT_TAG_SLUG = 'client';

    public function __construct(
        private ContactRepository $contactRepository,
        private ContactTagRepository $contactTagRepository,
        private EntityManagerInterface $entityManager,
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
    ) {}

    public function __invoke(OrderCreatedEvent $event): void
    {
        if ('1' !== $this->settingRepository->getOrDefault(CrmSettingEnum::SyncOrders)) {
            return;
        }

        $order = $event->getOrder();
        $email = $order->getEmail();
        if ('' === $email) {
            return;
        }

        [$firstName, $lastName] = $this->splitName($order->getName());

        $contact = $this->contactRepository->findOneBy(['email' => $email]);

        $clientTag = $this->contactTagRepository->findOneBySlug(self::CLIENT_TAG_SLUG);

        if (!$contact instanceof ContactInterface) {
            $contact = new Contact();
            $contact->setReference($this->sequenceGenerator->next(SequencePrefixEnum::Contact->value));
            $contact->setEmail($email);
            $contact->setFirstName($firstName);
            $contact->setLastName($lastName);
            $contact->setSource(ContactSourceEnum::Order);
            if ($clientTag instanceof ContactTagInterface) {
                $contact->addContactTag($clientTag);
            }

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

        if ($clientTag instanceof ContactTagInterface && !$this->hasTag($contact, $clientTag)) {
            $contact->addContactTag($clientTag);
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    private function hasTag(ContactInterface $contact, ContactTagInterface $tag): bool
    {
        foreach ($contact->getContactTags() as $existing) {
            if ($existing->getId() === $tag->getId()) {
                return true;
            }
        }

        return false;
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
