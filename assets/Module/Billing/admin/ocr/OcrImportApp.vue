<script setup>
import { ref, computed } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { useOcrJobs } from "@billing/admin/ocr/composables/useOcrJobs.js";
import { useOcrPreviews } from "@billing/admin/ocr/composables/useOcrPreviews.js";
import { useOcrUpload } from "@billing/admin/ocr/composables/useOcrUpload.js";
import { useOcrDelete } from "@billing/admin/ocr/composables/useOcrDelete.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppDropZone from "@/shared/components/form/AppDropZone.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { Eye, Trash2, RotateCcw, FileText, ScrollText, LayoutList, CircleCheck } from "lucide-vue-next";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { MimeType, isPdfMimeType, isImageMimeType } from "@core/utils/enums/media/mimeType.js";
import { OcrJobStatus, ACTIVE_STATUSES, RETRYABLE_STATUSES } from "@billing/utils/ocrJobStatus.js";

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
    invoiceShowPath: { type: String, required: true },
    statusUrlTemplate: { type: String, required: true },
    retryPath: { type: String, required: true },
    deletePath: { type: String, required: true },
    validatePathTemplate: { type: String, required: true },
});

const jobs = ref([...props.recentJobs]);
const { start: startPolling, retry: retryJob, pendingValidate, confirmValidate, validating: validatingJobId, hasInvoice } = useOcrJobs(jobs, { statusUrlTemplate: props.statusUrlTemplate, retryUrlTemplate: props.retryPath, validatePathTemplate: props.validatePathTemplate });

const { previews, scanFlashes, getPreviewJob, isJobScanning, removePreview, persistPreviews } = useOcrPreviews(jobs);
const { uploadingCount, onFileSelected } = useOcrUpload({ uploadPath: props.uploadPath, jobs, previews, persistPreviews, removePreview, startPolling });
const { pendingDelete, deleteLoading, deleteTiersToo, canDeleteTiers, logsJob, doDelete } = useOcrDelete({ deletePath: props.deletePath, jobs, previews, removePreview });

const previewCount = computed(() => previews.value.length);

const { formatDateTimeNumeric: formatDateTime } = useDateFormat();
</script>

<template>
    <div class="space-y-6">
        <div class="bg-surface border border-line/60 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-primary mb-2">{{ t('admin.billing.ocr.upload.title') }}</h3>
            <p class="text-sm text-secondary mb-4">{{ t('admin.billing.ocr.upload.help') }}</p>

            <AppDropZone
                :accept="OCR_ACCEPTED_MIME_TYPES"
                :uploading="uploadingCount > 0"
                hint="JPG, PNG, WebP, PDF"
                v-on:change="onFileSelected"
            />

            <!-- Previews grid -->
            <TransitionGroup
                v-if="previewCount"
                tag="div"
                name="ocr-preview"
                :class="['mt-4', previewCount > 1 ? 'grid grid-cols-2 sm:grid-cols-3 gap-3' : '']"
            >
                <div
                    v-for="preview in previews"
                    :key="preview.key"
                    class="relative rounded-lg overflow-hidden border border-line/60 bg-surface-2"
                >
                    <!-- Media -->
                    <img
                        v-if="isImageMimeType(preview.mime)"
                        :src="preview.url"
                        :class="['w-full object-contain', previewCount > 1 ? 'max-h-48' : 'max-h-96']"
                    >
                    <embed
                        v-else-if="isPdfMimeType(preview.mime)"
                        :src="preview.url"
                        :type="MimeType.Pdf"
                        :class="['w-full', previewCount > 1 ? 'h-48' : 'h-72']"
                    >
                    <div v-else class="flex flex-col items-center justify-center gap-2 text-muted" :class="previewCount > 1 ? 'h-48' : 'h-40'">
                        <FileText class="w-10 h-10" :stroke-width="1.5" />
                    </div>

                    <!-- Uploading spinner (before job is linked) -->
                    <div v-if="preview.jobId === null" class="absolute inset-0 bg-black/30 flex items-center justify-center">
                        <div class="w-6 h-6 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    </div>

                    <!-- Scanning overlay -->
                    <Transition v-else name="ocr-scan">
                        <div
                            v-if="isJobScanning(preview.jobId) || scanFlashes[preview.jobId]"
                            class="absolute inset-0 pointer-events-none"
                        >
                            <div
                                class="absolute inset-0 transition-colors duration-500"
                                :class="scanFlashes[preview.jobId] === 'error' ? 'bg-rose-500/20' : scanFlashes[preview.jobId] === 'success' ? 'bg-emerald-500/15' : 'bg-black/25'"
                            />

                            <template v-if="isJobScanning(preview.jobId)">
                                <div class="scan-bracket top-2 left-2 border-t-2 border-l-2" />
                                <div class="scan-bracket top-2 right-2 border-t-2 border-r-2" />
                                <div class="scan-bracket bottom-2 left-2 border-b-2 border-l-2" />
                                <div class="scan-bracket bottom-2 right-2 border-b-2 border-r-2" />
                                <div class="scan-line" />
                                <div class="absolute bottom-2 inset-x-0 flex justify-center">
                                    <span class="inline-flex items-center gap-1.5 bg-black/55 text-white text-xs px-2.5 py-1 rounded-full backdrop-blur-sm font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse shrink-0" />
                                        {{ getPreviewJob(preview.jobId)?.statusLabel }}
                                    </span>
                                </div>
                            </template>
                        </div>
                    </Transition>
                </div>
            </TransitionGroup>
        </div>

        <div class="flex justify-end">
            <AppButton variant="secondary" size="md" :href="jobsPath">
                <LayoutList class="w-4 h-4" :stroke-width="2" />
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
                                <AppBadge
                                    :color="job.status === OcrJobStatus.Completed && job.invoiceCanValidate ? 'amber'
                                        : job.status === OcrJobStatus.Completed && job.invoiceStatus === 'validated' ? 'emerald'
                                            : job.statusColor"
                                    :spinning="ACTIVE_STATUSES.has(job.status)"
                                >
                                    {{ job.status === OcrJobStatus.Completed && job.invoiceCanValidate ? t('admin.billing.ocr.status.readyToValidate')
                                        : job.status === OcrJobStatus.Completed && job.invoiceStatus === 'validated' ? t('admin.billing.invoices.status.validated')
                                            : job.statusLabel }}
                                </AppBadge>
                            </td>
                            <td class="px-6 py-3 text-secondary tabular-nums hidden md:table-cell">
                                {{ job.confidence !== null ? Math.round(job.confidence * 100) + '%' : '—' }}
                            </td>
                            <td class="px-6 py-3 text-xs text-muted hidden md:table-cell">{{ formatDateTime(job.createdAt) }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton v-if="hasInvoice(job) && job.isTerminal" color="sky" :title="t('shared.common.view')" :href="buildPath(invoiceShowPath, { id: job.invoiceId })">
                                        <Eye class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton v-if="job.invoiceCanValidate && job.status === OcrJobStatus.Completed" color="emerald" :title="t('admin.billing.invoices.show.validate')" v-on:click="pendingValidate = job">
                                        <CircleCheck class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton v-if="!job.isTerminal || (job.logs && job.logs.length) || job.error" color="slate" :title="t('admin.billing.ocr.viewLogs')" v-on:click="logsJob = job">
                                        <ScrollText class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton v-if="RETRYABLE_STATUSES.has(job.status)" color="amber" :title="t('admin.billing.ocr.retry')" v-on:click="retryJob(job)">
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

        <AppModal :show="!!logsJob" max-width="lg" v-on:close="logsJob = null">
            <h3 class="text-base font-semibold text-primary mb-3">
                {{ t('admin.billing.ocr.logsTitle') }} — #{{ logsJob?.id }}
                <AppBadge :color="logsJob?.statusColor" :spinning="logsJob && !logsJob.isTerminal" class="ml-2">{{ logsJob?.statusLabel }}</AppBadge>
            </h3>
            <div ref="logsContainer" class="bg-surface-2 rounded-lg p-3 h-72 overflow-y-auto scrollbar-thin font-mono text-xs space-y-1">
                <template v-if="!logsJob?.logs?.length">
                    <div v-if="logsJob?.status === OcrJobStatus.Queued" class="flex flex-col items-center gap-2 text-muted text-center py-8">
                        <span class="w-5 h-5 rounded-full border-2 border-muted border-t-transparent animate-spin" />
                        <span>{{ t('admin.billing.ocr.logsWaitingWorker') }}</span>
                    </div>
                    <div v-else-if="!logsJob?.isTerminal" class="flex flex-col items-center gap-2 text-muted text-center py-8">
                        <span class="w-5 h-5 rounded-full border-2 border-sky-400 border-t-transparent animate-spin" />
                        <span>{{ t('admin.billing.ocr.logsStarting') }}</span>
                    </div>
                    <pre v-else-if="logsJob?.error" class="text-rose-400 text-xs whitespace-pre-wrap break-all">{{ logsJob.error }}</pre>
                    <div v-else class="text-muted italic text-center py-8">{{ t('admin.billing.ocr.logsEmpty') }}</div>
                </template>
                <div
                    v-for="(entry, i) in logsJob?.logs"
                    :key="i"
                    class="flex items-start gap-2"
                >
                    <span class="shrink-0 text-muted">{{ new Date(entry.ts).toLocaleTimeString() }}</span>
                    <span
                        :class="{
                            'text-emerald-400': entry.level === 'info',
                            'text-amber-400': entry.level === 'warning',
                            'text-rose-400': entry.level === 'error',
                        }"
                    >{{ entry.message }}</span>
                </div>
                <div v-if="logsJob && !logsJob.isTerminal" class="flex items-center gap-1.5 text-muted animate-pulse pt-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400 shrink-0" />
                    <span>{{ t('admin.billing.ocr.logsRunning') }}</span>
                </div>
            </div>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="logsJob = null">{{ t('shared.common.close') }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <AppModal :show="!!pendingValidate" max-width="sm" v-on:close="pendingValidate = null">
            <p class="text-sm text-primary">{{ t('admin.billing.ocr.validateConfirm', { id: pendingValidate?.id ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.billing.ocr.validateHelp') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingValidate = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" size="md" :loading="!!validatingJobId" v-on:click="confirmValidate">{{ t('admin.billing.invoices.show.validate') }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="pendingDelete = null; deleteTiersToo = false">
            <p class="text-sm text-primary">{{ t('admin.billing.ocr.deleteConfirm', { id: pendingDelete?.id ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.billing.list.deleteWarning') }}</p>
            <label v-if="canDeleteTiers" class="flex items-center gap-2 mt-3 text-sm text-secondary cursor-pointer select-none">
                <input v-model="deleteTiersToo" type="checkbox" class="rounded border-line">
                {{ t('admin.billing.ocr.deleteTiersToo', { name: pendingDelete?.invoiceSupplierName ?? '' }) }}
            </label>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null; deleteTiersToo = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>

<style scoped>
.scan-line {
    position: absolute;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(to right, transparent 0%, #34d399 15%, #6ee7b7 50%, #34d399 85%, transparent 100%);
    box-shadow: 0 0 10px 3px rgba(52, 211, 153, 0.55), 0 0 28px 8px rgba(52, 211, 153, 0.25);
    animation: scanDown 1.8s cubic-bezier(0.45, 0, 0.55, 1) infinite;
}

@keyframes scanDown {
    0%   { top: 0%;   opacity: 0; }
    5%   { opacity: 1; }
    95%  { opacity: 1; }
    100% { top: 100%; opacity: 0; }
}

.scan-bracket {
    position: absolute;
    width: 16px;
    height: 16px;
    border-color: #34d399;
    animation: bracketPulse 1.8s ease-in-out infinite;
}

@keyframes bracketPulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.5; }
}

.ocr-preview-enter-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.ocr-preview-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.ocr-preview-enter-from   { opacity: 0; transform: scale(0.95); }
.ocr-preview-leave-to     { opacity: 0; transform: scale(0.95); }

.ocr-preview-move { transition: transform 0.3s ease; }

.ocr-scan-enter-active { transition: opacity 0.35s ease; }
.ocr-scan-leave-active { transition: opacity 0.6s ease; }
.ocr-scan-enter-from,
.ocr-scan-leave-to     { opacity: 0; }
</style>
