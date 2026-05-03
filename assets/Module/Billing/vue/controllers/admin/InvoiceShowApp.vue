<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useInlineEdit } from "@/shared/composables/form/useInlineEdit.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import InlineField from "@billing/vue/components/InlineField.vue";
import { Check, Trash2, Plus } from "lucide-vue-next";
import { formatCents, formatBpAsPercent } from "@/shared/utils/format/formatPrice.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { MimeType, isImageMimeType, isPdfMimeType } from "@core/utils/enums/media/mimeType.js";

const { t } = useI18n();

const props = defineProps({
    invoice: { type: Object, required: true },
    listPath: { type: String, required: true },
    validatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    updatePath: { type: String, required: true },
    supplierUpdatePathTemplate: { type: String, required: true },
    lineCreatePath: { type: String, required: true },
    lineUpdatePathTemplate: { type: String, required: true },
    lineDeletePathTemplate: { type: String, required: true },
});

const invoice = ref({ ...props.invoice });
const validating = ref(false);
const deleting = ref(false);
const showDeleteModal = ref(false);
const isNeedsReview = computed(() => invoice.value.status === 'needs_review');

const { saveField, submit } = useInlineEdit();

async function updateField(field, value) {
    const data = await saveField(props.updatePath, field, value);
    if (data) invoice.value = data.invoice;
}

async function validateInvoice() {
    if (validating.value) return;
    validating.value = true;
    const data = await submit(props.validatePath, null, { successMessage: 'admin.billing.invoices.show.validated' });
    if (data) invoice.value = data.invoice;
    validating.value = false;
}

async function deleteInvoice() {
    if (deleting.value) return;
    deleting.value = true;
    const data = await submit(props.deletePath, null, { successMessage: 'admin.billing.invoices.deleted' });
    deleting.value = false;
    showDeleteModal.value = false;
    if (data) window.location.href = props.listPath;
}

async function addLine() {
    const data = await submit(props.lineCreatePath, null, { successMessage: 'admin.billing.invoices.show.lineAdded' });
    if (data) invoice.value = data.invoice;
}

async function updateLineField(lineId, field, value) {
    const data = await saveField(buildPath(props.lineUpdatePathTemplate, { lineId }), field, value);
    if (data) invoice.value = data.invoice;
}

async function deleteLine(lineId) {
    const data = await submit(buildPath(props.lineDeletePathTemplate, { lineId }), null, {
        successMessage: 'admin.billing.invoices.show.lineDeleted',
    });
    if (data) invoice.value = data.invoice;
}

async function updateSupplierField(field, value) {
    const supplierId = invoice.value.supplierFull?.id;
    if (!supplierId) return;
    const data = await saveField(buildPath(props.supplierUpdatePathTemplate, { id: supplierId }), field, value);
    if (data) {
        invoice.value = { ...invoice.value, supplierFull: data.supplier, supplier: { id: data.supplier.id, name: data.supplier.name } };
    }
}

const { formatDateNumeric } = useDateFormat();
</script>

<template>
    <div class="space-y-4">
        <AppModal :show="showDeleteModal" max-width="sm" v-on:close="showDeleteModal = false">
            <p class="text-sm text-primary">{{ t('admin.billing.invoices.deleteConfirm', { number: invoice.number ?? ('#' + invoice.id) }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.billing.list.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="showDeleteModal = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleting" v-on:click="deleteInvoice">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-surface border border-line/60 rounded-xl p-6">
                    <h3 class="font-semibold text-primary mb-3">{{ t('admin.billing.invoices.show.document') }}</h3>
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
                    <AppNoData v-else :message="t('admin.billing.invoices.show.noDocument')" />
                </div>

                <div v-if="invoice.supplierFull" class="bg-surface border border-line/60 rounded-xl p-6 text-sm">
                    <h3 class="font-semibold text-primary mb-3">{{ t('admin.billing.invoices.show.supplier') }}</h3>
                    <dl class="space-y-2 text-secondary">
                        <div>
                            <dt class="text-xs text-muted">{{ t('admin.billing.suppliers.name') }}</dt>
                            <dd class="text-primary font-medium"><InlineField :display-value="invoice.supplierFull.name" :raw-value="invoice.supplierFull.name" type="text" v-on:save="updateSupplierField('name', $event)" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">{{ t('admin.billing.suppliers.vatNumber') }}</dt>
                            <dd class="font-mono text-xs"><InlineField :display-value="invoice.supplierFull.vatNumber" :raw-value="invoice.supplierFull.vatNumber" type="text" v-on:save="updateSupplierField('vatNumber', $event)" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">{{ t('admin.billing.suppliers.registrationNumber') }}</dt>
                            <dd class="font-mono text-xs"><InlineField :display-value="invoice.supplierFull.registrationNumber" :raw-value="invoice.supplierFull.registrationNumber" type="text" v-on:save="updateSupplierField('registrationNumber', $event)" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">IBAN</dt>
                            <dd class="font-mono text-xs break-all"><InlineField :display-value="invoice.supplierFull.iban" :raw-value="invoice.supplierFull.iban" type="text" v-on:save="updateSupplierField('iban', $event)" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">BIC</dt>
                            <dd class="font-mono text-xs"><InlineField :display-value="invoice.supplierFull.bic" :raw-value="invoice.supplierFull.bic" type="text" v-on:save="updateSupplierField('bic', $event)" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">{{ t('admin.billing.suppliers.email') }}</dt>
                            <dd class="text-xs break-all"><InlineField :display-value="invoice.supplierFull.email" :raw-value="invoice.supplierFull.email" type="text" v-on:save="updateSupplierField('email', $event)" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">{{ t('admin.billing.invoices.show.phone') }}</dt>
                            <dd><InlineField :display-value="invoice.supplierFull.phone" :raw-value="invoice.supplierFull.phone" type="text" v-on:save="updateSupplierField('phone', $event)" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">{{ t('admin.billing.suppliers.country') }}</dt>
                            <dd><InlineField :display-value="invoice.supplierFull.countryCode" :raw-value="invoice.supplierFull.countryCode" type="text" v-on:save="updateSupplierField('countryCode', $event)" /></dd>
                        </div>
                        <div class="pt-2 border-t border-line/60">
                            <dt class="text-xs text-muted mb-1">{{ t('admin.billing.invoices.show.address') }}</dt>
                            <dd class="text-primary text-xs"><InlineField :display-value="invoice.supplierFull.address" :raw-value="invoice.supplierFull.address" type="text" v-on:save="updateSupplierField('address', $event)" /></dd>
                        </div>
                    </dl>
                </div>

                <div v-if="invoice.ocrJob" class="bg-surface border border-line/60 rounded-xl p-6 text-sm">
                    <h3 class="font-semibold text-primary mb-3">{{ t('admin.billing.invoices.show.ocr') }}</h3>
                    <dl class="space-y-1.5 text-secondary">
                        <div class="flex justify-between gap-2"><dt>{{ t('admin.billing.invoices.show.ocrJob') }}</dt><dd class="text-right">#{{ invoice.ocrJob.id }}</dd></div>
                        <div class="flex justify-between gap-2"><dt>{{ t('admin.billing.ocr.statusLabel') }}</dt><dd class="text-right"><AppBadge :color="invoice.ocrJob.statusColor">{{ invoice.ocrJob.statusLabel }}</AppBadge></dd></div>
                        <div class="flex justify-between gap-2"><dt>{{ t('admin.billing.ocr.model') }}</dt><dd class="text-right text-xs">{{ invoice.ocrJob.modelUsed ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-2"><dt>{{ t('admin.billing.ocr.confidence') }}</dt><dd class="text-right tabular-nums">{{ invoice.ocrJob.confidence !== null ? Math.round(invoice.ocrJob.confidence * 100) + '%' : '—' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <div class="bg-surface border border-line/60 rounded-xl p-6">
                    <div class="flex items-start justify-between mb-4 gap-3">
                        <div>
                            <p class="text-xs text-muted uppercase tracking-wide">{{ t('admin.billing.invoices.statusLabel') }}</p>
                            <div class="mt-1">
                                <AppBadge :color="invoice.statusColor">{{ invoice.statusLabel }}</AppBadge>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <AppIconButton
                                v-if="isNeedsReview"
                                color="emerald"
                                :title="t('admin.billing.invoices.show.validate')"
                                :disabled="validating"
                                v-on:click="validateInvoice"
                            >
                                <Check class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="showDeleteModal = true">
                                <Trash2 class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('admin.billing.invoices.show.fields.number') }}</dt>
                            <dd class="text-primary font-medium">
                                <InlineField :display-value="invoice.number" :raw-value="invoice.number" type="text" v-on:save="updateField('number', $event)" />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('admin.billing.invoices.show.fields.purchaseOrder') }}</dt>
                            <dd class="text-primary">
                                <InlineField :display-value="invoice.purchaseOrderRef" :raw-value="invoice.purchaseOrderRef" type="text" v-on:save="updateField('purchaseOrderRef', $event)" />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('admin.billing.invoices.show.fields.issuedAt') }}</dt>
                            <dd class="text-primary">
                                <InlineField :display-value="formatDateNumeric(invoice.issuedAt)" :raw-value="invoice.issuedAt" type="date" v-on:save="updateField('issuedAt', $event)" />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('admin.billing.invoices.show.fields.dueAt') }}</dt>
                            <dd class="text-primary">
                                <InlineField :display-value="formatDateNumeric(invoice.dueAt)" :raw-value="invoice.dueAt" type="date" v-on:save="updateField('dueAt', $event)" />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('admin.billing.invoices.show.fields.paymentMethod') }}</dt>
                            <dd class="text-primary">
                                <InlineField :display-value="invoice.paymentMethod" :raw-value="invoice.paymentMethod" type="text" v-on:save="updateField('paymentMethod', $event)" />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1">{{ t('admin.billing.invoices.show.fields.paymentTerms') }}</dt>
                            <dd class="text-primary">
                                <InlineField :display-value="invoice.paymentTerms" :raw-value="invoice.paymentTerms" type="text" v-on:save="updateField('paymentTerms', $event)" />
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-6 pt-4 border-t border-line/60 grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1 text-right">{{ t('admin.billing.invoices.show.fields.totalNet') }}</dt>
                            <dd class="text-primary">
                                <InlineField
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
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1 text-right">{{ t('admin.billing.invoices.show.fields.totalVat') }}</dt>
                            <dd class="text-primary">
                                <InlineField
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
                            <dt class="text-muted text-xs uppercase tracking-wide mb-1 text-right">{{ t('admin.billing.invoices.show.fields.totalGross') }}</dt>
                            <dd class="text-primary font-semibold">
                                <InlineField
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
                        <h3 class="font-semibold text-primary">{{ t('admin.billing.invoices.show.lines') }} ({{ invoice.lines.length }})</h3>
                        <AppButton variant="ghost" size="sm" v-on:click="addLine">
                            <Plus class="w-4 h-4" :stroke-width="2" />
                            {{ t('admin.billing.invoices.show.addLine') }}
                        </AppButton>
                    </div>
                    <AppNoData v-if="!invoice.lines.length" :message="t('admin.billing.invoices.show.noLines')" />
                    <div v-else class="overflow-x-auto scrollbar-thin">
                        <table class="w-full text-sm">
                            <thead class="bg-surface-2 text-xs text-secondary uppercase tracking-wide">
                                <tr>
                                    <th class="text-left px-4 py-3 font-semibold">{{ t('admin.billing.invoices.show.lineCols.label') }}</th>
                                    <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">{{ t('admin.billing.invoices.show.lineCols.sku') }}</th>
                                    <th class="text-right px-4 py-3 font-semibold">{{ t('admin.billing.invoices.show.lineCols.qty') }}</th>
                                    <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">{{ t('admin.billing.invoices.show.lineCols.unit') }}</th>
                                    <th class="text-right px-4 py-3 font-semibold">{{ t('admin.billing.invoices.show.lineCols.unitPrice') }}</th>
                                    <th class="text-right px-4 py-3 font-semibold hidden lg:table-cell">{{ t('admin.billing.invoices.show.lineCols.vat') }}</th>
                                    <th class="text-right px-4 py-3 font-semibold">{{ t('admin.billing.invoices.show.lineCols.totalNet') }}</th>
                                    <th class="text-right px-4 py-3 font-semibold hidden lg:table-cell">{{ t('admin.billing.invoices.show.lineCols.totalGross') }}</th>
                                    <th class="text-right px-4 py-3 font-semibold">{{ t('shared.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="line in invoice.lines" :key="line.id" class="border-t border-line/60">
                                    <td class="px-4 py-3 text-primary">
                                        <InlineField :display-value="line.label" :raw-value="line.label" type="text" v-on:save="updateLineField(line.id, 'label', $event)" />
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-secondary hidden md:table-cell">
                                        <InlineField :display-value="line.sku" :raw-value="line.sku" type="text" v-on:save="updateLineField(line.id, 'sku', $event)" />
                                    </td>
                                    <td class="px-4 py-3 text-secondary">
                                        <InlineField
                                            align="right"
                                            :display-value="line.quantity"
                                            :raw-value="line.quantity"
                                            type="text"
                                            v-on:save="updateLineField(line.id, 'quantity', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted hidden md:table-cell">
                                        <InlineField :display-value="line.unit" :raw-value="line.unit" type="text" v-on:save="updateLineField(line.id, 'unit', $event)" />
                                    </td>
                                    <td class="px-4 py-3 text-secondary">
                                        <InlineField
                                            align="right"
                                            :display-value="formatCents(line.unitPriceCents)"
                                            :raw-value="line.unitPriceCents"
                                            type="money"
                                            v-on:save="updateLineField(line.id, 'unitPriceCents', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-secondary hidden lg:table-cell">
                                        <InlineField
                                            align="right"
                                            :display-value="formatBpAsPercent(line.vatRateBp)"
                                            :raw-value="line.vatRateBp"
                                            type="number"
                                            v-on:save="updateLineField(line.id, 'vatRateBp', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-primary">
                                        <InlineField
                                            align="right"
                                            :display-value="formatCents(line.totalNetCents)"
                                            :raw-value="line.totalNetCents"
                                            type="money"
                                            v-on:save="updateLineField(line.id, 'totalNetCents', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-primary hidden lg:table-cell">
                                        <InlineField
                                            align="right"
                                            :display-value="formatCents(line.totalGrossCents)"
                                            :raw-value="line.totalGrossCents"
                                            type="money"
                                            v-on:save="updateLineField(line.id, 'totalGrossCents', $event)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="deleteLine(line.id)">
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
