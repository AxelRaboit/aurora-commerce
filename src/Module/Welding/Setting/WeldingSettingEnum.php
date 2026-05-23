<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Setting;

use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

enum WeldingSettingEnum: string implements ApplicationParameterEnumInterface
{
    case ReferencePrefix = 'backend_welding_reference_prefix';
    case NotificationEmail = 'backend_welding_notification_email';
    case PdfDocumentPrefix = 'backend_welding_pdf_document_prefix';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ReferencePrefix => 'backend.parameters.welding_reference_prefix.label',
            self::NotificationEmail => 'backend.parameters.welding_notification_email.label',
            self::PdfDocumentPrefix => 'backend.parameters.welding_pdf_document_prefix.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ReferencePrefix => 'backend.parameters.welding_reference_prefix.description',
            self::NotificationEmail => 'backend.parameters.welding_notification_email.description',
            self::PdfDocumentPrefix => 'backend.parameters.welding_pdf_document_prefix.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::ReferencePrefix => 'WLD',
            self::NotificationEmail => '',
            self::PdfDocumentPrefix => SequencePrefixEnum::WeldingPdfDocument->value,
        };
    }

    public function getType(): string
    {
        return 'string';
    }

    public function getGroup(): string
    {
        return match ($this) {
            self::ReferencePrefix, self::PdfDocumentPrefix => 'sequences',
            self::NotificationEmail => 'welding',
        };
    }
}
