import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { OcrJobStatus } from "@billing/utils/ocrJobStatus.js";

const FIELD_LABELS = {
    supplier_name: "admin.billing.suppliers.name",
    supplier_vat_number: "admin.billing.suppliers.vatNumber",
    supplier_registration_number: "admin.billing.suppliers.registrationNumber",
    supplier_iban: "admin.billing.suppliers.iban",
    supplier_bic: "BIC",
    supplier_email: "admin.billing.suppliers.email",
    supplier_phone: "admin.billing.invoices.show.phone",
    supplier_address: "admin.billing.invoices.show.address",
    supplier_country_code: "admin.billing.suppliers.country",
    buyer_name: "admin.billing.suppliers.name",
    buyer_vat_number: "admin.billing.suppliers.vatNumber",
    buyer_address: "admin.billing.invoices.show.address",
    buyer_country_code: "admin.billing.suppliers.country",
    buyer_email: "admin.billing.suppliers.email",
    buyer_phone: "admin.billing.invoices.show.phone",
    invoice_number: "admin.billing.invoices.show.fields.supplierNumber",
    purchase_order_ref: "admin.billing.invoices.show.fields.purchaseOrder",
    issued_at: "admin.billing.invoices.show.fields.issuedAt",
    due_at: "admin.billing.invoices.show.fields.dueAt",
    payment_method: "admin.billing.invoices.show.fields.paymentMethod",
    payment_terms: "admin.billing.invoices.show.fields.paymentTerms",
    currency: "admin.billing.invoices.show.fields.currency",
    subtotal_cents: "admin.billing.invoices.show.fields.subtotal",
    total_net_cents: "admin.billing.invoices.show.fields.totalNet",
    total_vat_cents: "admin.billing.invoices.show.fields.totalVat",
    total_gross_cents: "admin.billing.invoices.show.fields.totalGross",
    discount_cents: "admin.billing.invoices.show.fields.discount",
    freight_cents: "admin.billing.invoices.show.fields.freight",
    insurance_cents: "admin.billing.invoices.show.fields.insurance",
    discount_rate_bp: "admin.billing.invoices.show.fields.discountRate",
    reference: "admin.billing.invoices.show.fields.reference",
    project: "admin.billing.invoices.show.fields.project",
    incoterms: "admin.billing.invoices.show.fields.incoterms",
    delivery_date: "admin.billing.invoices.show.fields.deliveryDate",
    reverse_charge: "admin.billing.invoices.show.fields.reverseCharge",
    bank_details: "admin.billing.invoices.show.fields.bankDetails",
    line_reference: "admin.billing.invoices.show.lineCols.reference",
    line_description: "admin.billing.invoices.show.lineCols.description",
    line_discount_cents: "admin.billing.invoices.show.lineCols.discount",
    line_origin: "admin.billing.invoices.show.lineCols.origin",
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
                    "admin.billing.invoices.show.ocrAnomaly.lowConfidence",
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
                        "admin.billing.invoices.show.ocrAnomaly.totalsMismatch",
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
                    "admin.billing.invoices.show.ocrAnomaly.uncertainFields",
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
            successMessage: "admin.billing.invoices.show.rescanQueued",
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
