<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Listener;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\Contact\Enum\ContactSourceEnum;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use Aurora\Module\Editorial\Form\Event\FormSubmissionCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Creates (or enriches) a CRM Contact when a form with crmSync enabled is submitted.
 *
 * Mapping rules (by field type):
 *   Email  → contact.email (also used to find an existing contact)
 *   Text   → if it's the first text field, split value into firstName / lastName
 *   Tel    → contact.phone
 */
#[AsEventListener]
final readonly class FormSubmissionCrmSyncListener
{
    public function __construct(
        private ContactRepository $contactRepository,
        private EntityManagerInterface $entityManager,
        private SequenceGenerator $sequenceGenerator,
    ) {}

    public function __invoke(FormSubmissionCreatedEvent $event): void
    {
        $form = $event->getForm();
        if (!$form->isCrmSync()) {
            return;
        }

        $submission = $event->getSubmission();
        $data = $submission->getData();

        $email = $this->extractByType($form, $data, FormFieldTypeEnum::Email);
        $fullName = $this->extractByType($form, $data, FormFieldTypeEnum::Text);
        $phone = $this->extractByType($form, $data, FormFieldTypeEnum::Tel);

        if (null === $email) {
            return;
        }

        $contact = $this->contactRepository->findOneBy(['email' => $email]);

        if (!$contact instanceof ContactInterface) {
            $contact = new Contact();
            $contact->setReference($this->sequenceGenerator->next(SequencePrefixEnum::Contact->value));
            $contact->setEmail($email);
            [$firstName, $lastName] = $this->splitName($fullName);
            $contact->setFirstName($firstName);
            $contact->setLastName($lastName);
            if (null !== $phone) {
                $contact->setPhone($phone);
            }

            $contact->setSource(ContactSourceEnum::Form);

            $this->entityManager->persist($contact);
            $this->entityManager->flush();
        } elseif (null !== $phone && null === $contact->getPhone()) {
            $contact->setPhone($phone);
            $this->entityManager->flush();
        }
    }

    private function extractByType(FormInterface $form, array $data, FormFieldTypeEnum $type): ?string
    {
        foreach ($form->getFields() as $field) {
            if ($field->getType() !== $type) {
                continue;
            }

            $value = $data[(string) $field->getId()] ?? null;
            if (is_string($value) && '' !== mb_trim($value)) {
                return mb_trim($value);
            }
        }

        return null;
    }

    /** @return array{string, string} [firstName, lastName] */
    private function splitName(?string $fullName): array
    {
        if (null === $fullName || '' === $fullName) {
            return ['', ''];
        }

        $parts = explode(' ', $fullName, 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
