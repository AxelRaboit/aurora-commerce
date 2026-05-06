<script setup>
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useInvoiceState } from "@billing/admin/invoice/composables/useInvoiceState.js";
import { useInvoiceActions } from "@billing/admin/invoice/composables/useInvoiceActions.js";
import { useInvoiceOcr } from "@billing/admin/invoice/composables/useInvoiceOcr.js";
import { useInvoiceCreditNote } from "@billing/admin/invoice/composables/useInvoiceCreditNote.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import InlineField from "@billing/components/InlineField.vue";
import { Check, Trash2, Plus, FileX, ExternalLink, ScanLine } from "lucide-vue-next";
import { formatCents, formatBpAsPercent } from "@/shared/utils/format/formatPrice.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { MimeType, isImageMimeType, isPdfMimeType } from "@core/utils/enums/media/mimeType.js";
import { OcrJobStatus } from "@billing/utils/ocrJobStatus.js";

const { t } = useI18n();

const props = defineProps({
    invoice: { type: Object, required: true },
    listPath: { type: String, required: true },
    validatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    updatePath: { type: String, required: true },
    tiersUpdatePathTemplate: { type: String, required: true },
    lineCreatePath: { type: String, required: true },
    lineUpdatePathTemplate: { type: String, required: true },
    lineDeletePathTemplate: { type: String, required: true },
    creditNotePath: { type: String, required: true },
    showPath: { type: String, required: true },
    importPath: { type: String, required: true },
    ocrRetryPath: { type: String, default: null },
});

const { invoice, validating, deleting, showDeleteModal, deleteTiersToo, deleteBuyerToo, canDeleteTiers, canDeleteBuyer, isNeedsReview, isLocked, isCreditNote, isCancelled, canHaveCreditNote } =
    useInvoiceState(props.invoice);

const { updateField, updateSupplierField, updateBuyerField, validateInvoice, deleteInvoice, addLine, updateLineField, deleteLine, submit } =
    useInvoiceActions(props, invoice, validating, deleting, showDeleteModal, deleteTiersToo, deleteBuyerToo);

const { fieldLabel, ocrAnomalies, rescanLoading, rescan } = useInvoiceOcr(invoice, props.ocrRetryPath, props.importPath, submit);

const { showCreditNoteModal, creditNoteReason, creatingCreditNote, createCreditNote } =
    useInvoiceCreditNote(props.creditNotePath, props.showPath, invoice, submit);

const { formatDateNumeric } = useDateFormat();
</script>

<template>
    <div class="space-y-4">
        <!-- Credit note modal -->
        <AppModal :show="showCreditNoteModal" max-width="sm" v-on:close="showCreditNoteModal = false">
            <h3 class="text-base font-semibold text-primary mb-1">{{ t('backend.billing.invoices.show.createCreditNote') }}</h3>
            <p class="text-sm text-secondary mb-4">{{ t('backend.billing.invoices.show.creditNoteHelp') }}</p>
            <textarea
                v-model="creditNoteReason"
                :placeholder="t('backend.billing.invoices.show.creditNoteReasonPlaceholder')"
                rows="3"
                class="w-full px-3 py-2 rounded-lg border border-line/60 bg-surface text-sm text-primary focus:outline-none focus:ring-2 focus:ring-accent-500/30 resize-none"
            />
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="showCreditNoteModal = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="creatingCreditNote" v-on:click="createCreditNote">
                    <FileX class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.billing.invoices.show.confirmCreditNote') }}
                </AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Banners -->
        <div v-if="isCancelled" class="flex items-center gap-3 bg-violet-500/10 border border-violet-500/30 rounded-lg px-4 py-3 text-sm">
            <FileX class="w-4 h-4 text-violet-400 shrink-0" :stroke-width="2" />
            <span class="text-primary">
                {{ t('backend.billing.invoices.show.cancelledBy') }}
                <a :href="buildPath(showPath, { id: invoice.creditNote.id })" class="font-medium text-accent-400 hover:text-accent-300 ml-1">
                    {{ invoice.creditNote.number ?? ('#' + invoice.creditNote.id) }}
                    <ExternalLink class="inline w-3 h-3 ml-0.5" :stroke-width="2" />
                </a>
            </span>
        </div>

        <div v-if="isCreditNote && invoice.cancelledInvoice" class="flex items-center gap-3 bg-violet-500/10 border border-violet-500/30 rounded-lg px-4 py-3 text-sm">
            <FileX class="w-4 h-4 text-violet-400 shrink-0" :stroke-width="2" />
            <span class="text-primary">
                {{ t('backend.billing.invoices.show.cancels') }}
                <a :href="buildPath(showPath, { id: invoice.cancelledInvoice.id })" class="font-medium text-accent-400 hover:text-accent-300 ml-1">
                    {{ invoice.cancelledInvoice.number ?? ('#' + invoice.cancelledInvoice.id) }}
                    <ExternalLink class="inline w-3 h-3 ml-0.5" :stroke-width="2" />
                </a>
            </span>
        </div>

        <AppModal :show="showDeleteModal" max-width="sm" v-on:close="showDeleteModal = false; deleteTiersToo = false; deleteBuyerToo = false">
            <p class="text-sm text-primary">{{ t('backend.billing.invoices.deleteConfirm', { number: invoice.number ?? ('#' + invoice.id) }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.billing.list.deleteWarning') }}</p>
            <div v-if="canDeleteTiers || canDeleteBuyer" class="mt-3 space-y-2">
                <label v-if="canDeleteTiers" class="flex items-center gap-2 text-sm text-secondary cursor-pointer select-none">
                    <input v-model="deleteTiersToo" type="checkbox" class="rounded border-line">
                    {{ t('backend.billing.invoices.deleteTiersToo', { name: invoice.supplier?.name ?? '' }) }}
                </label>
                <label v-if="canDeleteBuyer" class="flex items-center gap-2 text-sm text-secondary cursor-pointer select-none">
                    <input v-model="deleteBuyerToo" type="checkbox" class="rounded border-line">
                    {{ t('backend.billing.invoices.deleteBuyerToo', { name: invoice.buyer?.name ?? '' }) }}
                </label>
            </div>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="showDeleteModal = false; deleteTiersToo = false; deleteBuyerToo = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleting" v-on:click="deleteInvoice">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-4">
                <div v-if="invoice.ocrJob" class="bg-surface border border-line/60 rounded-xl p-6 text-sm">
                    <h3 class="font-semibold text-primary mb-3">{{ t('backend.billing.invoices.show.ocr') }}</h3>
                    <dl class="space-y-1.5 text-secondary">
                        <div class="flex justify-between gap-2"><dt>{{ t('backend.billing.invoices.show.ocrJob') }}</dt><dd class="text-right">#{{ invoice.ocrJob.id }}</dd></div>
                        <div class="flex justify-between gap-2"><dt>{{ t('backend.billing.ocr.statusLabel') }}</dt><dd class="text-right"><AppBadge :color="invoice.ocrJob.statusColor">{{ invoice.ocrJob.statusLabel }}</AppBadge></dd></div>
                        <div class="flex justify-between gap-2"><dt>{{ t('backend.billing.ocr.model') }}</dt><dd class="text-right text-xs">{{ invoice.ocrJob.modelUsed ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-2"><dt>{{ t('backend.billing.ocr.confidence') }}</dt><dd class="text-right tabular-nums">{{ invoice.ocrJob.confidence !== null ? Math.round(invoice.ocrJob.confidence * 100) + '%' : '—' }}</dd></div>
                    </dl>
                    <ul v-if="ocrAnomalies.length" class="mt-3 space-y-1.5">
                        <li v-for="anomaly in ocrAnomalies" :key="anomaly.text" class="text-xs bg-amber-500/10 rounded-lg px-3 py-2 space-y-1.5">
                            <div class="flex items-start gap-2 text-amber-400">
                                <span class="shrink-0 mt-0.5">⚠</span>
                                <span>{{ anomaly.text }}</span>
                            </div>
                            <div v-if="anomaly.fields.length" class="flex flex-wrap gap-1 pl-5">
                                <span
                                    v-for="field in anomaly.fields"
                                    :key="field"
                                    class="bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full text-xs font-medium"
                                >{{ field }}</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="bg-surface border border-line/60 rounded-xl p-6">
                    <h3 class="font-semibold text-primary mb-3">{{ t('backend.billing.invoices.show.document') }}</h3>
                    <template v-if="invoice.document">
                        <a
                            v-if="isImageMimeType(invoice.document.mimeType)"
                            :href="invoice.document.url"
                            target="_blank"
                            rel="noopener"
                            class="block"
                        >
                            <img :src="invoice.document.url" :alt="invoice.document.originalName" class="w-full rounded-lg border border-line/60">
                        </a>
                        <embed
                            v-else-if="isPdfMimeType(invoice.document.mimeType)"
                            :src="invoice.document.url"
                            :type="MimeType.Pdf"
                            class="w-full h-96 rounded-lg border border-line/60"
                        >
                        <a
                            v-else
                            :href="invoice.document.url"
                            target="_blank"
                            rel="noopener"
                            class="text-accent-400 hover:text-accent-300 text-sm"
                        >
                            {{ invoice.document.originalName }} →
                        </a>
                        <p class="mt-2 text-xs text-muted truncate">{{ invoice.document.originalName }}</p>
                    </template>
                    <AppNoData v-else :message="t('backend.billing.invoices.show.noDocument')" />
                </div>

                <div v-if="invoice.supplierFull" class="bg-surface border border-line/60 rounded-xl p-6 text-sm">
                    <h3 class="font-semibold text-primary mb-3">{{ t('backend.billing.invoices.show.supplier') }}</h3>
                    <dl class="space-y-2 text-secondary">
                        <div>
                            <dt class="text-xs text-muted">{{ t('backend.billing.suppliers.name') }}</dt>
                            <dd class="text-primary font-medium">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.supplierFull.name"
                                    :raw-value="invoice.supplierFull.name"
                                    type="text"
                                    v-on:save="updateSupplierField('name', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">{{ t('backend.billing.suppliers.vatNumber') }}</dt>
                            <dd class="font-mono text-xs">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.supplierFull.vatNumber"
                                    :raw-value="invoice.supplierFull.vatNumber"
                                    type="text"
                                    v-on:save="updateSupplierField('vatNumber', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">{{ t('backend.billing.suppliers.registrationNumber') }}</dt>
                            <dd class="font-mono text-xs">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.supplierFull.registrationNumber"
                                    :raw-value="invoice.supplierFull.registrationNumber"
                                    type="text"
                                    v-on:save="updateSupplierField('registrationNumber', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">IBAN</dt>
                            <dd class="font-mono text-xs break-all">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.supplierFull.iban"
                                    :raw-value="invoice.supplierFull.iban"
                                    type="text"
                                    v-on:save="updateSupplierField('iban', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">BIC</dt>
                            <dd class="font-mono text-xs">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.supplierFull.bic"
                                    :raw-value="invoice.supplierFull.bic"
                                    type="text"
                                    v-on:save="updateSupplierField('bic', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">{{ t('backend.billing.suppliers.email') }}</dt>
                            <dd class="text-primary break-all">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.supplierFull.email"
                                    :raw-value="invoice.supplierFull.email"
                                    type="text"
                                    v-on:save="updateSupplierField('email', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">{{ t('backend.billing.invoices.show.phone') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.supplierFull.phone"
                                    :raw-value="invoice.supplierFull.phone"
                                    type="text"
                                    v-on:save="updateSupplierField('phone', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">{{ t('backend.billing.suppliers.country') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.supplierFull.countryCode"
                                    :raw-value="invoice.supplierFull.countryCode"
                                    type="text"
                                    v-on:save="updateSupplierField('countryCode', $event)"
                                />
                            </dd>
                        </div>
                        <div class="pt-2 border-t border-line/60">
                            <dt class="text-xs text-muted mb-1">{{ t('backend.billing.invoices.show.address') }}</dt>
                            <dd class="text-primary text-xs">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.supplierFull.address"
                                    :raw-value="invoice.supplierFull.address"
                                    type="text"
                                    v-on:save="updateSupplierField('address', $event)"
                                />
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-surface border border-line/60 rounded-xl p-6 text-sm">
                    <h3 class="font-semibold text-primary mb-3">{{ t('backend.billing.invoices.show.buyer') }}</h3>
                    <template v-if="invoice.buyer">
                        <dl class="space-y-2 text-secondary">
                            <div>
                                <dt class="text-xs text-muted">{{ t('backend.billing.suppliers.name') }}</dt>
                                <dd class="text-primary font-medium">
                                    <InlineField
                                        :display-value="invoice.buyer?.name"
                                        :raw-value="invoice.buyer?.name"
                                        type="text"
                                        v-on:save="updateBuyerField('name', $event)"
                                    />
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted">{{ t('backend.billing.suppliers.vatNumber') }}</dt>
                                <dd class="font-mono text-xs">
                                    <InlineField
                                        :display-value="invoice.buyer?.vatNumber"
                                        :raw-value="invoice.buyer?.vatNumber"
                                        type="text"
                                        v-on:save="updateBuyerField('vatNumber', $event)"
                                    />
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted">{{ t('backend.billing.suppliers.country') }}</dt>
                                <dd>
                                    <InlineField
                                        :display-value="invoice.buyer?.countryCode"
                                        :raw-value="invoice.buyer?.countryCode"
                                        type="text"
                                        v-on:save="updateBuyerField('countryCode', $event)"
                                    />
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted">{{ t('backend.billing.suppliers.email') }}</dt>
                                <dd class="text-primary break-all">
                                    <InlineField
                                        :display-value="invoice.buyer?.email"
                                        :raw-value="invoice.buyer?.email"
                                        type="text"
                                        v-on:save="updateBuyerField('email', $event)"
                                    />
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted">{{ t('backend.billing.invoices.show.phone') }}</dt>
                                <dd class="text-primary">
                                    <InlineField
                                        :display-value="invoice.buyer?.phone"
                                        :raw-value="invoice.buyer?.phone"
                                        type="text"
                                        v-on:save="updateBuyerField('phone', $event)"
                                    />
                                </dd>
                            </div>
                            <div class="pt-2 border-t border-line/60">
                                <dt class="text-xs text-muted mb-1">{{ t('backend.billing.invoices.show.address') }}</dt>
                                <dd class="text-primary text-xs">
                                    <InlineField
                                        :display-value="invoice.buyer?.address"
                                        :raw-value="invoice.buyer?.address"
                                        type="text"
                                        v-on:save="updateBuyerField('address', $event)"
                                    />
                                </dd>
                            </div>
                        </dl>
                    </template>
                    <p v-else class="text-xs text-muted italic">{{ t('backend.billing.invoices.show.noBuyer') }}</p>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <div class="bg-surface border border-line/60 rounded-xl p-6">
                    <div class="flex items-start justify-between mb-4 gap-3">
                        <div>
                            <p class="text-xs text-muted uppercase tracking-wide">{{ t('backend.billing.invoices.statusLabel') }}</p>
                            <div class="mt-1">
                                <AppBadge :color="invoice.statusColor">{{ invoice.statusLabel }}</AppBadge>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <AppIconButton
                                v-if="isNeedsReview"
                                color="emerald"
                                :title="t('backend.billing.invoices.show.validate')"
                                :disabled="validating"
                                v-on:click="validateInvoice"
                            >
                                <Check class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton
                                v-if="isNeedsReview && ocrRetryPath"
                                color="sky"
                                :title="t('backend.billing.invoices.show.rescan')"
                                :loading="rescanLoading"
                                v-on:click="rescan"
                            >
                                <ScanLine class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton v-if="canHaveCreditNote" color="violet" :title="t('backend.billing.invoices.show.createCreditNote')" v-on:click="showCreditNoteModal = true">
                                <FileX class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton v-if="invoice.isDeletable" color="rose" :title="t('shared.common.delete')" v-on:click="showDeleteModal = true">
                                <Trash2 class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.number') }}</dt>
                            <dd class="text-primary font-medium font-mono text-xs">
                                <span v-if="invoice.number" class="text-accent-400">{{ invoice.number }}</span>
                                <span v-else class="text-muted italic text-xs">{{ t('backend.billing.invoices.show.numberPending') }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.supplierNumber') }}</dt>
                            <dd class="text-primary font-mono text-xs">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.supplierNumber"
                                    :raw-value="invoice.supplierNumber"
                                    type="text"
                                    v-on:save="updateField('supplierNumber', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.purchaseOrder') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.purchaseOrderRef"
                                    :raw-value="invoice.purchaseOrderRef"
                                    type="text"
                                    v-on:save="updateField('purchaseOrderRef', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.issuedAt') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="formatDateNumeric(invoice.issuedAt)"
                                    :raw-value="invoice.issuedAt"
                                    type="date"
                                    v-on:save="updateField('issuedAt', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.dueAt') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="formatDateNumeric(invoice.dueAt)"
                                    :raw-value="invoice.dueAt"
                                    type="date"
                                    v-on:save="updateField('dueAt', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.paymentMethod') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.paymentMethod"
                                    :raw-value="invoice.paymentMethod"
                                    type="text"
                                    v-on:save="updateField('paymentMethod', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.paymentTerms') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.paymentTerms"
                                    :raw-value="invoice.paymentTerms"
                                    type="text"
                                    v-on:save="updateField('paymentTerms', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.reference') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.reference"
                                    :raw-value="invoice.reference"
                                    type="text"
                                    v-on:save="updateField('reference', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.project') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.project"
                                    :raw-value="invoice.project"
                                    type="text"
                                    v-on:save="updateField('project', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.deliveryDate') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.deliveryDate"
                                    :raw-value="invoice.deliveryDate"
                                    type="text"
                                    v-on:save="updateField('deliveryDate', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.incoterms') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.incoterms"
                                    :raw-value="invoice.incoterms"
                                    type="text"
                                    v-on:save="updateField('incoterms', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.reverseCharge') }}</dt>
                            <dd class="text-primary text-sm">
                                <label class="flex items-center gap-2 cursor-pointer select-none mt-1">
                                    <input
                                        type="checkbox"
                                        :checked="!!invoice.reverseCharge"
                                        :disabled="isLocked"
                                        class="rounded border-line"
                                        v-on:change="updateField('reverseCharge', $event.target.checked)"
                                    >
                                    <span class="text-xs text-secondary">{{ invoice.reverseCharge ? t('shared.common.yes') : t('shared.common.no') }}</span>
                                </label>
                            </dd>
                        </div>
                        <div class="col-span-3">
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.fields.bankDetails') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    :display-value="invoice.bankDetails"
                                    :raw-value="invoice.bankDetails"
                                    type="text"
                                    v-on:save="updateField('bankDetails', $event)"
                                />
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1 text-right">{{ t('backend.billing.invoices.show.fields.subtotal') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    align="right"
                                    :display-value="formatCents(invoice.subtotalCents)"
                                    :raw-value="invoice.subtotalCents"
                                    type="money"
                                    :currency="invoice.currency"
                                    v-on:save="updateField('subtotalCents', $event)"
                                />
                            </dd>
                        </div>
                        <div class="col-span-2" />
                    </div>

                    <div class="mt-2 grid grid-cols-3 gap-4 text-sm">
                        <!-- Remise : montant + taux regroupés dans la même colonne -->
                        <div class="space-y-2">
                            <div>
                                <dt class="text-muted text-xs uppercase tracking-wide mb-1 text-right">{{ t('backend.billing.invoices.show.fields.discount') }}</dt>
                                <dd class="text-primary">
                                    <InlineField
                                        :disabled="isLocked"
                                        align="right"
                                        :display-value="invoice.discountCents != null ? formatCents(invoice.discountCents) : null"
                                        :raw-value="invoice.discountCents"
                                        type="money"
                                        :currency="invoice.currency"
                                        v-on:save="updateField('discountCents', $event)"
                                    />
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted text-xs uppercase tracking-wide mb-1 text-right">{{ t('backend.billing.invoices.show.fields.discountRate') }}</dt>
                                <dd class="text-primary">
                                    <InlineField
                                        :disabled="isLocked"
                                        align="right"
                                        :display-value="invoice.discountRateBp != null ? (invoice.discountRateBp / 100).toFixed(2) + ' %' : null"
                                        :raw-value="invoice.discountRateBp"
                                        type="number"
                                        v-on:save="updateField('discountRateBp', $event)"
                                    />
                                </dd>
                            </div>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1 text-right">{{ t('backend.billing.invoices.show.fields.freight') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    align="right"
                                    :display-value="invoice.freightCents != null ? formatCents(invoice.freightCents) : null"
                                    :raw-value="invoice.freightCents"
                                    type="money"
                                    :currency="invoice.currency"
                                    v-on:save="updateField('freightCents', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1 text-right">{{ t('backend.billing.invoices.show.fields.insurance') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    align="right"
                                    :display-value="invoice.insuranceCents != null ? formatCents(invoice.insuranceCents) : null"
                                    :raw-value="invoice.insuranceCents"
                                    type="money"
                                    :currency="invoice.currency"
                                    v-on:save="updateField('insuranceCents', $event)"
                                />
                            </dd>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-line/60 grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1 text-right">{{ t('backend.billing.invoices.show.fields.totalNet') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    align="right"
                                    :display-value="formatCents(invoice.totalNetCents)"
                                    :raw-value="invoice.totalNetCents"
                                    type="money"
                                    :currency="invoice.currency"
                                    v-on:save="updateField('totalNetCents', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1 text-right">{{ t('backend.billing.invoices.show.fields.totalVat') }}</dt>
                            <dd class="text-primary">
                                <InlineField
                                    :disabled="isLocked"
                                    align="right"
                                    :display-value="formatCents(invoice.totalVatCents)"
                                    :raw-value="invoice.totalVatCents"
                                    type="money"
                                    :currency="invoice.currency"
                                    v-on:save="updateField('totalVatCents', $event)"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1 text-right">{{ t('backend.billing.invoices.show.fields.totalGross') }}</dt>
                            <dd class="text-primary font-semibold">
                                <InlineField
                                    :disabled="isLocked"
                                    align="right"
                                    :display-value="formatCents(invoice.totalGrossCents)"
                                    :raw-value="invoice.totalGrossCents"
                                    type="money"
                                    :currency="invoice.currency"
                                    v-on:save="updateField('totalGrossCents', $event)"
                                />
                            </dd>
                        </div>
                    </div>
                </div>

                <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-line/60 flex items-center justify-between">
                        <h3 class="font-semibold text-primary">{{ t('backend.billing.invoices.show.lines') }} ({{ invoice.lines.length }})</h3>
                        <AppButton v-if="!isLocked" variant="ghost" size="sm" v-on:click="addLine">
                            <Plus class="w-4 h-4" :stroke-width="2" />
                            {{ t('backend.billing.invoices.show.addLine') }}
                        </AppButton>
                    </div>
                    <AppNoData v-if="!invoice.lines.length" :message="t('backend.billing.invoices.show.noLines')" />
                    <div v-else class="overflow-x-auto scrollbar-thin">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-surface-2/50 border-b border-line/40">
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.invoices.show.lineCols.label') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden xl:table-cell">{{ t('backend.billing.invoices.show.lineCols.reference') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.billing.invoices.show.lineCols.productCode') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.invoices.show.lineCols.qty') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.billing.invoices.show.lineCols.unit') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.invoices.show.lineCols.unitPrice') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted hidden xl:table-cell">{{ t('backend.billing.invoices.show.lineCols.discount') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('backend.billing.invoices.show.lineCols.vat') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.invoices.show.lineCols.totalNet') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('backend.billing.invoices.show.lineCols.totalGross') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/40">
                                <tr v-for="line in invoice.lines" :key="line.id" class="group hover:bg-surface-2/40 transition-colors">
                                    <td class="px-4 py-3 text-primary">
                                        <InlineField
                                            :disabled="isLocked"
                                            :display-value="line.label"
                                            :raw-value="line.label"
                                            type="text"
                                            v-on:save="updateLineField(line.id, 'label', $event)"
                                        />
                                        <div v-if="line.description" class="text-xs text-muted mt-0.5 truncate max-w-xs">{{ line.description }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-secondary hidden xl:table-cell">
                                        <InlineField
                                            :disabled="isLocked"
                                            :display-value="line.reference"
                                            :raw-value="line.reference"
                                            type="text"
                                            v-on:save="updateLineField(line.id, 'reference', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-secondary hidden md:table-cell">
                                        <InlineField
                                            :disabled="isLocked"
                                            :display-value="line.productCode"
                                            :raw-value="line.productCode"
                                            type="text"
                                            v-on:save="updateLineField(line.id, 'productCode', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-secondary">
                                        <InlineField
                                            :disabled="isLocked"
                                            align="right"
                                            :display-value="line.quantity"
                                            :raw-value="line.quantity"
                                            type="text"
                                            v-on:save="updateLineField(line.id, 'quantity', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted hidden md:table-cell">
                                        <InlineField
                                            :disabled="isLocked"
                                            :display-value="line.unit"
                                            :raw-value="line.unit"
                                            type="text"
                                            v-on:save="updateLineField(line.id, 'unit', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-secondary">
                                        <InlineField
                                            :disabled="isLocked"
                                            align="right"
                                            :display-value="formatCents(line.unitPriceCents)"
                                            :raw-value="line.unitPriceCents"
                                            type="money"
                                            v-on:save="updateLineField(line.id, 'unitPriceCents', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-secondary hidden xl:table-cell">
                                        <InlineField
                                            :disabled="isLocked"
                                            align="right"
                                            :display-value="line.discountCents != null ? formatCents(line.discountCents) : null"
                                            :raw-value="line.discountCents"
                                            type="money"
                                            :currency="invoice.currency"
                                            v-on:save="updateLineField(line.id, 'discountCents', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-secondary hidden lg:table-cell">
                                        <InlineField
                                            :disabled="isLocked"
                                            align="right"
                                            :display-value="formatBpAsPercent(line.vatRateBp)"
                                            :raw-value="line.vatRateBp"
                                            type="number"
                                            v-on:save="updateLineField(line.id, 'vatRateBp', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-primary">
                                        <InlineField
                                            :disabled="isLocked"
                                            align="right"
                                            :display-value="formatCents(line.totalNetCents)"
                                            :raw-value="line.totalNetCents"
                                            type="money"
                                            v-on:save="updateLineField(line.id, 'totalNetCents', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-primary hidden lg:table-cell">
                                        <InlineField
                                            :disabled="isLocked"
                                            align="right"
                                            :display-value="formatCents(line.totalGrossCents)"
                                            :raw-value="line.totalGrossCents"
                                            type="money"
                                            v-on:save="updateLineField(line.id, 'totalGrossCents', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <AppIconButton v-if="!isLocked" color="rose" :title="t('shared.common.delete')" v-on:click="deleteLine(line.id)">
                                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                                        </AppIconButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
