<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Service;

use Aurora\Core\Mail\Service\MailService;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;

final readonly class CrmNotificationService
{
    public function __construct(private MailService $mail) {}

    public function notifyContactCreated(Contact $contact): void
    {
        $this->mail->sendToAdmin(
            'crm.mail.subject_contact_new',
            '@Crm/email/contact_created.html.twig',
            ['contact' => $contact],
        );
    }

    public function notifyDealStageChanged(Deal $deal, DealStageEnum $newStage): void
    {
        if (DealStageEnum::Won !== $newStage && DealStageEnum::Lost !== $newStage) {
            return;
        }

        $subjectKey = DealStageEnum::Won === $newStage
            ? 'crm.mail.subject_deal_won'
            : 'crm.mail.subject_deal_lost';

        $this->mail->sendToAdmin(
            $subjectKey,
            '@Crm/email/deal_stage_changed.html.twig',
            ['deal' => $deal, 'newStage' => $newStage],
        );
    }
}
