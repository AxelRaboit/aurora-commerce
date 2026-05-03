<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useOcrJobs } from "@billing/vue/composables/useOcrJobs.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppDropZone from "@/shared/components/form/AppDropZone.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { Eye, Trash2, RotateCcw } from "lucide-vue-next";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { MimeType } from "@core/utils/enums/media/mimeType.js";

const OCR_ACCEPTED_MIME_TYPES = [
    MimeType.Jpeg,
    MimeType.Png,
    MimeType.Webp,
    MimeType.Pdf,
].join(",");

const { t } = useI18n();

const props = defineProps({
    recentJobs: { type: Array, default: () => [] },
    uploadPath: { type: String, required: true },
    jobsPath: { type: String, required: true },
    invoicesPath: { type: String, required: true },
    statusUrlTemplate: { type: String, required: true },
    retryPath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const jobs = ref([...props.recentJobs]);
const uploading = ref(false);
const { request } = useApiRequest();
const { start: startPolling, retry: retryJob, hasInvoice } = useOcrJobs(jobs, {
    statusUrlTemplate: props.statusUrlTemplate,
    retryUrlTemplate: props.retryPath,
});

async function onFileSelected(file) {
    if (!file) return;
    uploading.value = true;
    try {
        const form = new FormData();
        form.append("document", file);
        // Multipart upload bypasses useApiRequest (which sets JSON Content-Type).
        const res = await fetch(props.uploadPath, { method: HttpMethod.Post, body: form });
        const data = await res.json();
        if (!data.success) {
            toast.error(t(data.errors?.document ?? data.error ?? "shared.common.error"));
            return;
        }
        toast.success(t("admin.billing.ocr.upload.success", { id: data.job.id }));
        jobs.value = [data.job, ...jobs.value].slice(0, 10);
        startPolling();
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        uploading.value = false;
    }
}

onMounted(startPolling);

const pendingDelete = ref(null);
const deleteLoading = ref(false);

async function doDelete() {
    if (deleteLoading.value || !pendingDelete.value) return;
    deleteLoading.value = true;
    const data = await request(buildPath(props.deletePath, { id: pendingDelete.value.id }));
    deleteLoading.value = false;
    if (!data?.success) { toast.error(t(data?.error ?? 'shared.common.error')); return; }
    jobs.value = jobs.value.filter(j => j.id !== pendingDelete.value.id);
    pendingDelete.value = null;
    toast.success(t('admin.billing.ocr.deleted'));
}

const { formatDateTimeNumeric: formatDateTime } = useDateFormat();
</script>

<template>
    <div class="space-y-6">
        <div class="bg-surface border border-line/60 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-primary mb-2">{{ t('admin.billing.ocr.upload.title') }}</h3>
            <p class="text-sm text-secondary mb-4">{{ t('admin.billing.ocr.upload.help') }}</p>

            <AppDropZone
                :accept="OCR_ACCEPTED_MIME_TYPES"
                :uploading="uploading"
                hint="JPG, PNG, WebP, PDF"
                v-on:change="onFileSelected"
            />
        </div>

        <div class="flex justify-end">
            <AppButton variant="secondary" size="md" :href="jobsPath">
                {{ t('admin.billing.ocr.upload.allJobs') }}
            </AppButton>
        </div>

        <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <AppNoData v-if="!jobs.length" :message="t('admin.billing.ocr.empty')" />
            <div v-else class="overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.ocr.fileName') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.ocr.statusLabel') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.billing.ocr.confidence') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.billing.ocr.createdAt') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="job in jobs" :key="job.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-6 py-3 font-mono text-xs text-secondary">{{ job.id }}</td>
                            <td class="px-6 py-3 text-primary font-medium truncate max-w-xs">{{ job.fileName }}</td>
                            <td class="px-6 py-3">
                                <AppBadge :color="job.statusColor">{{ job.statusLabel }}</AppBadge>
                            </td>
                            <td class="px-6 py-3 text-secondary tabular-nums hidden md:table-cell">
                                {{ job.confidence !== null ? Math.round(job.confidence * 100) + '%' : '—' }}
                            </td>
                            <td class="px-6 py-3 text-xs text-muted hidden md:table-cell">{{ formatDateTime(job.createdAt) }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton v-if="hasInvoice(job)" color="sky" :title="t('shared.common.view')" :href="`${invoicesPath}?search=${job.id}`">
                                        <Eye class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton v-if="job.status === 'failed'" color="amber" :title="t('admin.billing.ocr.retry')" v-on:click="retryJob(job)">
                                        <RotateCcw class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="pendingDelete = job">
                                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

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
