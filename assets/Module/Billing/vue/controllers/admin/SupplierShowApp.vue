<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useInlineEdit } from "@/shared/composables/form/useInlineEdit.js";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import InlineField from "@billing/vue/components/InlineField.vue";
import { Eye, Trash2 } from "lucide-vue-next";
import { formatCents } from "@/shared/utils/format/formatPrice.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";

const { t } = useI18n();

const props = defineProps({
    supplier: { type: Object, required: true },
    invoices: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
    listPath: { type: String, required: true },
    invoicesListPath: { type: String, required: true },
    invoiceShowPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    statusOptions: { type: Array, default: () => [] },
});

const supplier = ref({ ...props.supplier });
const showDeleteModal = ref(false);
const deleting = ref(false);
const { saveField, submit } = useInlineEdit();

async function updateField(field, value) {
    const data = await saveField(props.updatePath, field, value);
    if (data) supplier.value = data.supplier;
}

async function deleteSupplier() {
    if (deleting.value) return;
    deleting.value = true;
    const data = await submit(props.deletePath, null, { successMessage: 'admin.billing.suppliers.deleted' });
    deleting.value = false;
    showDeleteModal.value = false;
    if (data) window.location.href = props.listPath;
}

const statusFilter = ref("");

const { items, page, totalPages, total, search, onSearch, goToPage, reload } = useListPage(
    props.invoicesListPath,
    {
        initialData: props.invoices,
        extraParams: () => ({
            supplier: props.supplier.id,
            status: statusFilter.value || undefined,
        }),
    },
);

function onStatusChange() { reload(); }

const STATUS_SELECT = computed(() =>
    props.statusOptions.map(option => ({ value: option.value, label: t(option.labelKey) })),
);

const { formatDateNumeric } = useDateFormat();
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-surface border border-line/60 rounded-xl px-5 py-4">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('admin.billing.suppliers.show.totalInvoiced') }}</p>
                    <p class="text-xl font-semibold text-primary tabular-nums mt-1">{{ formatCents(stats.totalInvoiced) ?? '—' }} EUR</p>
                </div>
                <div class="bg-surface border border-line/60 rounded-xl px-5 py-4">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('admin.billing.suppliers.show.invoiceCount') }}</p>
                    <p class="text-xl font-semibold text-primary tabular-nums mt-1">{{ stats.invoiceCount }}</p>
                </div>
            </div>
            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="showDeleteModal = true">
                <Trash2 class="w-4 h-4" :stroke-width="2" />
            </AppIconButton>
        </div>

        <div class="bg-surface border border-line/60 rounded-xl p-6">
            <h3 class="font-semibold text-primary mb-4">{{ t('admin.billing.suppliers.show.details') }}</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.billing.suppliers.name') }}</dt>
                    <dd class="text-primary font-medium">
                        <InlineField :display-value="supplier.name" :raw-value="supplier.name" type="text" v-on:save="updateField('name', $event)" />
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.billing.suppliers.vatNumber') }}</dt>
                    <dd class="text-primary"><InlineField :display-value="supplier.vatNumber" :raw-value="supplier.vatNumber" type="text" v-on:save="updateField('vatNumber', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.billing.suppliers.registrationNumber') }}</dt>
                    <dd class="text-primary"><InlineField :display-value="supplier.registrationNumber" :raw-value="supplier.registrationNumber" type="text" v-on:save="updateField('registrationNumber', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">IBAN</dt>
                    <dd class="text-primary"><InlineField :display-value="supplier.iban" :raw-value="supplier.iban" type="text" v-on:save="updateField('iban', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">BIC</dt>
                    <dd class="text-primary"><InlineField :display-value="supplier.bic" :raw-value="supplier.bic" type="text" v-on:save="updateField('bic', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.billing.suppliers.country') }}</dt>
                    <dd class="text-primary"><InlineField :display-value="supplier.countryCode" :raw-value="supplier.countryCode" type="text" v-on:save="updateField('countryCode', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.billing.suppliers.email') }}</dt>
                    <dd class="text-primary"><InlineField :display-value="supplier.email" :raw-value="supplier.email" type="text" v-on:save="updateField('email', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.billing.invoices.show.phone') }}</dt>
                    <dd class="text-primary"><InlineField :display-value="supplier.phone" :raw-value="supplier.phone" type="text" v-on:save="updateField('phone', $event)" /></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.billing.invoices.show.address') }}</dt>
                    <dd class="text-primary"><InlineField :display-value="supplier.address" :raw-value="supplier.address" type="text" v-on:save="updateField('address', $event)" /></dd>
                </div>
            </dl>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-primary">{{ t('admin.billing.suppliers.show.invoices') }} ({{ total }})</h3>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex-1 max-w-md">
                    <AppSearchInput v-model="search" :placeholder="t('admin.billing.invoices.searchPlaceholder')" v-on:search="onSearch" />
                </div>
                <AppMultiselect
                    v-model="statusFilter"
                    :options="STATUS_SELECT"
                    :placeholder="t('admin.billing.list.allStatuses')"
                    :allow-empty="true"
                    class="sm:max-w-xs"
                    v-on:update:model-value="onStatusChange"
                />
            </div>

            <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
                <AppNoData v-if="!items?.length" :message="t('admin.billing.invoices.empty')" />
                <table v-else class="w-full text-sm">
                    <thead class="bg-surface-2 text-xs text-secondary uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold">{{ t('admin.billing.invoices.number') }}</th>
                            <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">{{ t('admin.billing.invoices.issuedAt') }}</th>
                            <th class="text-left px-4 py-3 font-semibold hidden lg:table-cell">{{ t('admin.billing.invoices.dueAt') }}</th>
                            <th class="text-right px-4 py-3 font-semibold">{{ t('admin.billing.invoices.totalGross') }}</th>
                            <th class="text-left px-4 py-3 font-semibold">{{ t('admin.billing.invoices.statusLabel') }}</th>
                            <th class="text-right px-4 py-3 font-semibold">{{ t('shared.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="invoice in items" :key="invoice.id" class="border-t border-line/60 hover:bg-surface-2/50 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-primary">{{ invoice.number ?? '—' }}</td>
                            <td class="px-4 py-3 text-secondary hidden md:table-cell">{{ formatDateNumeric(invoice.issuedAt) }}</td>
                            <td class="px-4 py-3 text-secondary hidden lg:table-cell">{{ formatDateNumeric(invoice.dueAt) }}</td>
                            <td class="px-4 py-3 text-primary text-right tabular-nums">{{ formatCents(invoice.totalGrossCents, invoice.currency) ?? '—' }}</td>
                            <td class="px-4 py-3"><AppBadge :color="invoice.statusColor">{{ invoice.statusLabel ?? t(`admin.billing.invoices.status.${invoice.status}`) }}</AppBadge></td>
                            <td class="px-4 py-3 text-right">
                                <AppIconButton color="sky" :title="t('shared.common.view')" :href="buildPath(invoiceShowPath, { id: invoice.id })">
                                    <Eye class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />
        </div>

        <AppModal :show="showDeleteModal" max-width="sm" v-on:close="showDeleteModal = false">
            <p class="text-sm text-primary">{{ t('admin.billing.suppliers.deleteConfirm', { name: supplier.name }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.billing.list.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="showDeleteModal = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleting" v-on:click="deleteSupplier">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
