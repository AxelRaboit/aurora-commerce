<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Listener;

use Aurora\Core\Contact\Event\ContactSignalEvent;
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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Creates (or enriches) a CRM Contact from a cross-module
 * {@see ContactSignalEvent} (ecommerce order, editorial form submission, …).
 *
 * Decoupled by design: this listener depends only on the core event, never
 * on the producing module — so Ecommerce/Editorial and Crm split into
 * separate Composer packages without a sideways dependency.
 *
 * Match strategy: locate an existing contact by email; if found, enrich
 * missing fields and add missing tags only; otherwise create one.
 *
 * Source-specific gating:
 *  - `order` signals are opt-in via the `crm_sync_orders` setting (a pure
 *    B2C shop may not want every buyer as a contact);
 *  - other sources are assumed already gated by their producer (e.g. a
 *    form's `crmSync` flag), so they always apply.
 */
#[AsEventListener]
final readonly class ContactSignalListener
{
    public function __construct(
        private ContactRepository $contactRepository,
        private ContactTagRepository $contactTagRepository,
        private EntityManagerInterface $entityManager,
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
    ) {}

    public function __invoke(ContactSignalEvent $event): void
    {
        if (ContactSourceEnum::Order->value === $event->getSourceKey()
            && '1' !== $this->settingRepository->getOrDefault(CrmSettingEnum::SyncOrders)) {
            return;
        }

        $email = $event->getEmail();
        if ('' === $email) {
            return;
        }

        $source = ContactSourceEnum::tryFrom($event->getSourceKey()) ?? ContactSourceEnum::Manual;
        [$firstName, $lastName] = $this->splitName($event->getFullName());
        $phone = $event->getPhone();
        $tags = $this->resolveTags($event->getTagSlugs());

        $contact = $this->contactRepository->findOneBy(['email' => $email]);

        if (!$contact instanceof ContactInterface) {
            $contact = new Contact();
            $contact->setReference($this->sequenceGenerator->next(SequencePrefixEnum::Contact->value));
            $contact->setEmail($email);
            $contact->setFirstName($firstName);
            $contact->setLastName($lastName);
            if (null !== $phone) {
                $contact->setPhone($phone);
            }

            $contact->setSource($source);
            foreach ($tags as $tag) {
                $contact->addContactTag($tag);
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

        if (null !== $phone && null === $contact->getPhone()) {
            $contact->setPhone($phone);
            $dirty = true;
        }

        foreach ($tags as $tag) {
            if (!$this->hasTag($contact, $tag)) {
                $contact->addContactTag($tag);
                $dirty = true;
            }
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param list<string> $slugs
     *
     * @return list<ContactTagInterface>
     */
    private function resolveTags(array $slugs): array
    {
        $tags = [];
        foreach ($slugs as $slug) {
            $tag = $this->contactTagRepository->findOneBySlug($slug);
            if ($tag instanceof ContactTagInterface) {
                $tags[] = $tag;
            }
        }

        return $tags;
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

    /** @return array{string, string} [firstName, lastName] */
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
