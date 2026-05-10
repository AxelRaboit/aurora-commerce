<?php

declare(strict_types=1);

namespace Aurora\Core\Sequence;

/**
 * Default prefixes for all sequential business numbers.
 * Each case maps to an ApplicationParameterEnum setting that can override
 * the default at runtime via /admin/settings → Séquences.
 */
enum SequencePrefixEnum: string
{
    case Invoice = 'FAC';
    case CreditNote = 'AV';
    case Order = 'ORD';
    case Product = 'PROD';
    case Deal = 'DEAL';
    case Contact = 'CTT';
    case Company = 'CPY';
    case Listing = 'LST';
    case Gallery = 'GAL';
    case Post = 'ART';
    case Form = 'FRM';
    case Tiers = 'TRS';
    case User = 'USR';
    case Media = 'MED';
    case AccessRequest = 'ACR';
    case FormSubmission = 'SUB';
    case GalleryItem = 'PHO';
    case GalleryInvite = 'GIV';
    case Comment = 'CMT';
    case AuditLog = 'LOG';
    case ResetPasswordRequest = 'RPR';
    case MediaFolder = 'MFD';
    case MenuItem = 'MNI';
    case OcrJob = 'OCR';
    case Cart = 'CRT';
    case CartItem = 'CRI';
    case OrderLine = 'ORL';
    case FormField = 'FLD';
    case TaxonomyTerm = 'TRM';
    case GalleryFinalization = 'GFN';
    case GalleryItemComment = 'GIC';
    case GalleryPick = 'GPK';
    case GedDocument = 'DOC';
    case PdfFormDocument = 'PDF';
    case Project = 'PRJ';
    case ProjectTask = 'TSK';
    case ProjectColumn = 'PRJC';
    case Planning = 'PLN';
    case PlanningEvent = 'PEV';
    case Employee = 'EMP';
}
