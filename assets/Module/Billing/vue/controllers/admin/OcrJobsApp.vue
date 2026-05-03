<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useOcrJobs } from "@billing/vue/composables/useOcrJobs.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { Plus, Eye, Trash2, RotateCcw, Info } from "lucide-vue-next";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";

const { t } = useI18n();

const props = defineProps({
    jobs: { type: Object, default: () => ({}) },
    listPath: { type: String, required: true },
    statusUrlTemplate: { type: String, required: true },
    retryPath: { type: String, required: true },
    deletePath: { type: String, required: true },
    invoicesPath: { type: String, required: true },
    importPath: { type: String, required: true },
    statusOptions: { type: Array, default: () => [] },
});

const statusFilter = ref("");

const { items, page, totalPages, goToPage, reload, load } = useListPage(
    props.listPath,
    {
        initialData: props.jobs,
        searchParam: "search",
        extraParams: () => ({ status: statusFilter.value || undefined }),
    },
);

const STATUS_SELECT = props.statusOptions.map(option => ({ value: option.value, label: option.label }));

function onStatusChange() {
    reload();
}

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => reload(), 'admin.billing.ocr.deleted',
);

const errorJob = ref(null);

const { start: startPolling, retry: retryJob, hasInvoice } = useOcrJobs(items, {
    statusUrlTemplate: props.statusUrlTemplate,
    retryUrlTemplate: props.retryPath,
});

const { formatDateTimeNumeric: formatDateTime } = useDateFormat();

onMounted(startPolling);
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppMultiselect
                v-model="statusFilter"
                :options="STATUS_SELECT"
                :placeholder="t('admin.billing.list.allStatuses')"
                :allow-empty="true"
                v-on:update:model-value="onStatusChange"
            />
            <AppButton variant="primary" size="md" :href="importPath">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.billing.ocr.import') }}
            </AppButton>
        </div>

        <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <AppNoData v-if="!items?.length" :message="t('admin.billing.ocr.empty')" />
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.ocr.fileName') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.ocr.statusLabel') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.billing.ocr.confidence') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('admin.billing.ocr.model') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.billing.ocr.createdAt') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="job in items" :key="job.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3 font-mono text-xs text-secondary">{{ job.id }}</td>
                        <td class="px-6 py-3 text-primary font-medium truncate max-w-xs">{{ job.fileName }}</td>
                        <td class="px-6 py-3">
                            <AppBadge :color="job.statusColor">{{ job.statusLabel }}{{ job.progress !== null ? ` ${job.progress}%` : '' }}</AppBadge>
                        </td>
                        <td class="px-6 py-3 text-secondary tabular-nums hidden md:table-cell">
                            {{ job.confidence !== null ? Math.round(job.confidence * 100) + '%' : '—' }}
                        </td>
                        <td class="px-6 py-3 text-xs text-muted hidden lg:table-cell">{{ job.modelUsed ?? '—' }}</td>
                        <td class="px-6 py-3 text-xs text-muted hidden md:table-cell">{{ formatDateTime(job.createdAt) }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="hasInvoice(job)" color="sky" :title="t('shared.common.view')" :href="`${invoicesPath}?search=${job.id}`">
                                    <Eye class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="job.status === 'failed'" color="sky" :title="t('admin.billing.ocr.errorLog')" v-on:click="errorJob = job">
                                    <Info class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="job.status === 'failed'" color="amber" :title="t('admin.billing.ocr.retry')" v-on:click="retryJob(job)">
                                    <RotateCcw class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(job)">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />

        <AppModal :show="!!errorJob" max-width="md" v-on:close="errorJob = null">
            <h3 class="text-base font-semibold text-primary mb-3">{{ t('admin.billing.ocr.errorLog') }} — #{{ errorJob?.id }}</h3>
            <pre class="text-xs text-secondary bg-surface-2 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap break-all">{{ errorJob?.error ?? t('admin.billing.ocr.noErrorLog') }}</pre>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="errorJob = null">{{ t('shared.common.close') }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="pendingDelete = null">
            <p class="text-sm text-primary">{{ t('admin.billing.ocr.deleteConfirm', { id: pendingDelete?.id ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.billing.list.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
