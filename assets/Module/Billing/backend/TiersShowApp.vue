<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useTiersActions } from "./composables/useTiersActions.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import InlineField from "@billing/backend/components/InlineField.vue";
import { Eye, Trash2, X } from "lucide-vue-next";
import { formatCents } from "@/shared/utils/format/formatPrice.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";

const { t } = useI18n();

const props = defineProps({
    tiers: { type: Object, required: true },
    invoices: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
    listPath: { type: String, required: true },
    invoicesListPath: { type: String, required: true },
    invoiceShowPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    statusOptions: { type: Array, default: () => [] },
});

const { tiers, showDeleteModal, deleting, updateField, deleteTiers } = useTiersActions(props);

const statusFilter = ref("");

const { items, page, totalPages, total, search, onSearch, goToPage, reload } = useListPage(
    props.invoicesListPath,
    {
        initialData: props.invoices,
        extraParams: () => ({
            tiers: props.tiers.id,
            status: statusFilter.value || undefined,
        }),
    },
);

function onStatusChange() { reload(); }

const STATUS_SELECT = computed(() =>
    props.statusOptions.map(option => ({ value: option.value, label: t(option.labelKey) })),
);

const { formatDateNumeric } = useDateFormat();

const isSupplier = computed(() => tiers.value.type === 'supplier');
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-surface border border-line/60 rounded-xl px-5 py-4">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('backend.billing.suppliers.show.totalInvoiced') }}</p>
                    <p class="text-xl font-semibold text-primary tabular-nums mt-1">{{ formatCents(stats.totalInvoiced) ?? '—' }} EUR</p>
                </div>
                <div class="bg-surface border border-line/60 rounded-xl px-5 py-4">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('backend.billing.suppliers.show.invoiceCount') }}</p>
                    <p class="text-xl font-semibold text-primary tabular-nums mt-1">{{ stats.invoiceCount }}</p>
                </div>
            </div>
            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="showDeleteModal = true">
                <Trash2 class="w-4 h-4" :stroke-width="2" />
            </AppIconButton>
        </div>

        <div class="bg-surface border border-line/60 rounded-xl p-6">
            <h3 class="font-semibold text-primary mb-4">{{ t('backend.billing.suppliers.show.details') }}</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.billing.suppliers.name') }}</dt>
                    <dd class="text-primary font-medium">
                        <InlineField :display-value="tiers.name" :raw-value="tiers.name" type="text" v-on:save="updateField('name', $event)" />
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.billing.suppliers.vatNumber') }}</dt>
                    <dd><InlineField :display-value="tiers.vatNumber" :raw-value="tiers.vatNumber" type="text" v-on:save="updateField('vatNumber', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.billing.suppliers.registrationNumber') }}</dt>
                    <dd><InlineField :display-value="tiers.registrationNumber" :raw-value="tiers.registrationNumber" type="text" v-on:save="updateField('registrationNumber', $event)" /></dd>
                </div>
                <div v-if="isSupplier">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">IBAN</dt>
                    <dd><InlineField :display-value="tiers.iban" :raw-value="tiers.iban" type="text" v-on:save="updateField('iban', $event)" /></dd>
                </div>
                <div v-if="isSupplier">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">BIC</dt>
                    <dd><InlineField :display-value="tiers.bic" :raw-value="tiers.bic" type="text" v-on:save="updateField('bic', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.billing.suppliers.country') }}</dt>
                    <dd><InlineField :display-value="tiers.countryCode" :raw-value="tiers.countryCode" type="text" v-on:save="updateField('countryCode', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.billing.suppliers.email') }}</dt>
                    <dd><InlineField :display-value="tiers.email" :raw-value="tiers.email" type="text" v-on:save="updateField('email', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.phone') }}</dt>
                    <dd><InlineField :display-value="tiers.phone" :raw-value="tiers.phone" type="text" v-on:save="updateField('phone', $event)" /></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.billing.invoices.show.address') }}</dt>
                    <dd><InlineField :display-value="tiers.address" :raw-value="tiers.address" type="text" v-on:save="updateField('address', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.billing.suppliers.legalForm') }}</dt>
                    <dd><InlineField :display-value="tiers.legalForm" :raw-value="tiers.legalForm" type="text" v-on:save="updateField('legalForm', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.billing.suppliers.website') }}</dt>
                    <dd><InlineField :display-value="tiers.website" :raw-value="tiers.website" type="text" v-on:save="updateField('website', $event)" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.billing.suppliers.bankName') }}</dt>
                    <dd><InlineField :display-value="tiers.bankName" :raw-value="tiers.bankName" type="text" v-on:save="updateField('bankName', $event)" /></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.billing.suppliers.notes') }}</dt>
                    <dd><InlineField :display-value="tiers.notes" :raw-value="tiers.notes" type="text" v-on:save="updateField('notes', $event)" /></dd>
                </div>
            </dl>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-primary">{{ t('backend.billing.suppliers.show.invoices') }} ({{ total }})</h3>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex-1 max-w-md">
                    <AppSearchInput v-model="search" :placeholder="t('backend.billing.invoices.searchPlaceholder')" v-on:search="onSearch" />
                </div>
                <AppMultiselect
                    v-model="statusFilter"
                    :options="STATUS_SELECT"
                    :placeholder="t('backend.billing.list.allStatuses')"
                    :allow-empty="true"
                    class="sm:max-w-xs"
                    v-on:update:model-value="onStatusChange"
                />
            </div>

            <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
                <AppNoData v-if="!items?.length" :message="t('backend.billing.invoices.empty')" />
                <table v-else class="w-full text-sm">
                    <thead class="bg-surface-2 text-xs text-secondary uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold">{{ t('backend.billing.invoices.number') }}</th>
                            <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">{{ t('backend.billing.invoices.issuedAt') }}</th>
                            <th class="text-right px-4 py-3 font-semibold">{{ t('backend.billing.invoices.totalGross') }}</th>
                            <th class="text-left px-4 py-3 font-semibold">{{ t('backend.billing.invoices.statusLabel') }}</th>
                            <th class="text-right px-4 py-3 font-semibold">{{ t('shared.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="invoice in items" :key="invoice.id" class="border-t border-line/60 hover:bg-surface-2/50 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-primary">{{ invoice.number ?? '—' }}</td>
                            <td class="px-4 py-3 text-secondary hidden md:table-cell">{{ formatDateNumeric(invoice.issuedAt) }}</td>
                            <td class="px-4 py-3 text-primary text-right tabular-nums">{{ formatCents(invoice.totalGrossCents, invoice.currency) ?? '—' }}</td>
                            <td class="px-4 py-3"><AppBadge :color="invoice.statusColor">{{ invoice.statusLabel ?? t(`backend.billing.invoices.status.${invoice.status}`) }}</AppBadge></td>
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
            <p class="text-sm text-primary">{{ t('backend.billing.tiers.deleteConfirm', { name: tiers.name }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.billing.list.deleteWarning') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showDeleteModal = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleting" v-on:click="deleteTiers"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
