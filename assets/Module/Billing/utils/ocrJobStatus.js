export const OcrJobStatus = Object.freeze({
    Queued:      'queued',
    Extracting:  'extracting',
    Parsing:     'parsing',
    Completed:   'completed',
    NeedsReview: 'needs_review',
    Failed:      'failed',
});

export const ACTIVE_STATUSES    = new Set([OcrJobStatus.Extracting, OcrJobStatus.Parsing]);
export const RETRYABLE_STATUSES = new Set([OcrJobStatus.Failed, OcrJobStatus.NeedsReview]);
export const INVOICE_STATUSES   = new Set([OcrJobStatus.Completed, OcrJobStatus.NeedsReview]);
