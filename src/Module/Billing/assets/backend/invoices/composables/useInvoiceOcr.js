import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { OcrJobStatus } from "@billing/backend/utils/ocrJobStatus.js";

const FIELD_LABELS = {
    supplier_name: "backend.billing.suppliers.name",
    supplier_vat_number: "backend.billing.suppliers.vat_number",
    supplier_registration_number:
        "backend.billing.suppliers.registration_number",
    supplier_iban: "backend.billing.suppliers.iban",
    supplier_bic: "BIC",
    supplier_email: "backend.billing.suppliers.email",
    supplier_phone: "backend.billing.invoices.show.phone",
    supplier_address: "backend.billing.invoices.show.address",
    supplier_country_code: "backend.billing.suppliers.country",
    buyer_name: "backend.billing.suppliers.name",
    buyer_vat_number: "backend.billing.suppliers.vat_number",
    buyer_address: "backend.billing.invoices.show.address",
    buyer_country_code: "backend.billing.suppliers.country",
    buyer_email: "backend.billing.suppliers.email",
    buyer_phone: "backend.billing.invoices.show.phone",
    invoice_number: "backend.billing.invoices.show.fields.supplier_number",
    purchase_order_ref: "backend.billing.invoices.show.fields.purchase_order",
    issued_at: "backend.billing.invoices.show.fields.issued_at",
    due_at: "backend.billing.invoices.show.fields.due_at",
    payment_method: "backend.billing.invoices.show.fields.payment_method",
    payment_terms: "backend.billing.invoices.show.fields.payment_terms",
    currency: "backend.billing.invoices.show.fields.currency",
    subtotal_cents: "backend.billing.invoices.show.fields.subtotal",
    total_net_cents: "backend.billing.invoices.show.fields.total_net",
    total_vat_cents: "backend.billing.invoices.show.fields.total_vat",
    total_gross_cents: "backend.billing.invoices.show.fields.total_gross",
    discount_cents: "backend.billing.invoices.show.fields.discount",
    freight_cents: "backend.billing.invoices.show.fields.freight",
    insurance_cents: "backend.billing.invoices.show.fields.insurance",
    discount_rate_bp: "backend.billing.invoices.show.fields.discount_rate",
    reference: "backend.billing.invoices.show.fields.reference",
    project: "backend.billing.invoices.show.fields.project",
    incoterms: "backend.billing.invoices.show.fields.incoterms",
    delivery_date: "backend.billing.invoices.show.fields.delivery_date",
    reverse_charge: "backend.billing.invoices.show.fields.reverse_charge",
    bank_details: "backend.billing.invoices.show.fields.bank_details",
    line_reference: "backend.billing.invoices.show.line_cols.reference",
    line_description: "backend.billing.invoices.show.line_cols.description",
    line_discount_cents: "backend.billing.invoices.show.line_cols.discount",
    line_origin: "backend.billing.invoices.show.line_cols.origin",
};

export function useInvoiceOcr(invoice, ocrRetryPath, importPath, submit) {
    const { t } = useI18n();

    function fieldLabel(key) {
        const tk = FIELD_LABELS[key];
        if (!tk) return key;
        const translated = t(tk);
        return translated !== tk ? translated : key;
    }

    const ocrAnomalies = computed(() => {
        const reasons = [];
        const job = invoice.value.ocrJob;
        if (!job || job.status !== OcrJobStatus.NeedsReview) return reasons;
        if (job.confidence !== null && job.confidence < 0.85) {
            reasons.push({
                text: t(
                    "backend.billing.invoices.show.ocr_anomaly.low_confidence",
                    { pct: Math.round(job.confidence * 100), threshold: 85 },
                ),
                fields: (job.uncertainFields ?? []).map(fieldLabel),
            });
        }
        const {
            totalNetCents: net,
            totalVatCents: vat,
            totalGrossCents: gross,
        } = invoice.value;
        if (net !== null && vat !== null && gross !== null) {
            const diff = Math.abs(net + vat - gross);
            if (diff > 200)
                reasons.push({
                    text: t(
                        "backend.billing.invoices.show.ocr_anomaly.totals_mismatch",
                        {
                            expected: ((net + vat) / 100).toFixed(2),
                            actual: (gross / 100).toFixed(2),
                            diff: (diff / 100).toFixed(2),
                            currency: invoice.value.currency,
                        },
                    ),
                    fields: [
                        fieldLabel("total_net_cents"),
                        fieldLabel("total_vat_cents"),
                        fieldLabel("total_gross_cents"),
                    ],
                });
        }
        if (
            job.confidence !== null &&
            job.confidence >= 0.85 &&
            (job.uncertainFields ?? []).length
        ) {
            reasons.push({
                text: t(
                    "backend.billing.invoices.show.ocr_anomaly.uncertain_fields",
                ),
                fields: job.uncertainFields.map(fieldLabel),
            });
        }
        return reasons;
    });

    const rescanLoading = ref(false);
    async function rescan() {
        if (!ocrRetryPath || rescanLoading.value) return;
        rescanLoading.value = true;
        const data = await submit(ocrRetryPath, null, {
            successMessage: "backend.billing.invoices.show.rescan_queued",
        });
        rescanLoading.value = false;
        if (data) window.location.href = importPath;
        if (data)
            invoice.value = {
                ...invoice.value,
                ocrJob: {
                    ...invoice.value.ocrJob,
                    status: "queued",
                    statusLabel:
                        data.job?.statusLabel ??
                        invoice.value.ocrJob?.statusLabel,
                    statusColor:
                        data.job?.statusColor ??
                        invoice.value.ocrJob?.statusColor,
                },
            };
    }

    return { fieldLabel, ocrAnomalies, rescanLoading, rescan };
}
