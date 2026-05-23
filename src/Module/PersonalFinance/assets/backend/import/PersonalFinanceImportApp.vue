<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { Upload, Download, FileSpreadsheet, CheckCircle2, AlertTriangle, RotateCcw, Play } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import { useImportFlow } from "./composables/useImportFlow.js";

const props = defineProps({
    wallets: { type: Array, required: true },
    previewPath: { type: String, required: true },
    processPath: { type: String, required: true },
    templatePath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();

const walletOptions = computed(() => props.wallets.map((w) => ({ value: w.id, label: w.name })));

const fileInput = ref(null);
const fileName = ref("");

const {
    step,
    walletId,
    file,
    preview,
    report,
    fileError,
    canConfirm,
    loading,
    submitUpload,
    confirmProcess,
    reset,
} = useImportFlow({ previewPath: props.previewPath, processPath: props.processPath });

walletId.value = props.wallets[0]?.id ?? null;

function onFilePicked(event) {
    const target = event.target;
    const picked = target.files?.[0] ?? null;
    file.value = picked;
    fileName.value = picked?.name ?? "";
}

function downloadTemplate() {
    window.location.assign(props.templatePath);
}

function rowClasses(row) {
    return row.valid ? "" : "bg-rose-500/5";
}
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <div class="flex items-center gap-3 text-sm text-muted">
                <span :class="step === 'upload' ? 'text-primary font-medium' : ''">1. {{ t("personal_finance.import.step_upload") }}</span>
                <span>→</span>
                <span :class="step === 'preview' ? 'text-primary font-medium' : ''">2. {{ t("personal_finance.import.step_preview") }}</span>
                <span>→</span>
                <span :class="step === 'done' ? 'text-primary font-medium' : ''">3. {{ t("personal_finance.import.step_done") }}</span>
            </div>
            <template #actions>
                <AppButton variant="ghost" size="md" v-on:click="downloadTemplate">
                    <Download class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.import.download_template") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <AppMessage variant="info">
            {{ t("personal_finance.import.help") }}
        </AppMessage>

        <!-- Step 1: upload -->
        <section v-if="step === 'upload'" class="bg-surface border border-line rounded-lg p-6 space-y-4">
            <h3 class="text-sm font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.import.step_upload") }}</h3>

            <div v-if="!wallets.length" class="text-sm text-muted">
                {{ t("personal_finance.import.no_wallet") }}
            </div>

            <template v-else>
                <AppMultiselect
                    v-model="walletId"
                    :label="`${t('personal_finance.import.fields.wallet')} *`"
                    :options="walletOptions"
                    :allow-empty="false"
                />

                <div>
                    <label class="block text-xs uppercase tracking-wider text-muted mb-1">{{ `${t('personal_finance.import.fields.file')} *` }}</label>
                    <div class="flex items-center gap-3">
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".xlsx"
                            class="hidden"
                            v-on:change="onFilePicked"
                        />
                        <AppButton variant="secondary" size="md" v-on:click="fileInput?.click()">
                            <FileSpreadsheet class="w-4 h-4" :stroke-width="2" />
                            {{ t("personal_finance.import.pick_file") }}
                        </AppButton>
                        <span class="text-sm text-muted truncate">{{ fileName || t("personal_finance.import.no_file") }}</span>
                    </div>
                    <p v-if="fileError" class="text-xs text-rose-400 mt-2">{{ fileError }}</p>
                </div>

                <div class="flex justify-end pt-2 border-t border-line">
                    <AppButton variant="primary" size="md" :loading="loading" :disabled="!walletId || !file" v-on:click="submitUpload">
                        <Upload class="w-4 h-4" :stroke-width="2" />
                        {{ t("personal_finance.import.preview_button") }}
                    </AppButton>
                </div>
            </template>
        </section>

        <!-- Step 2: preview -->
        <section v-if="step === 'preview' && preview" class="bg-surface border border-line rounded-lg p-6 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.import.step_preview") }}</h3>
                <AppButton variant="ghost" size="sm" v-on:click="reset">
                    <RotateCcw class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("personal_finance.import.reset") }}
                </AppButton>
            </div>

            <AppMessage v-if="preview.fatalErrors.length" variant="error">
                <ul class="space-y-1">
                    <li v-for="(err, idx) in preview.fatalErrors" :key="`fe-${idx}`" class="text-sm">{{ err }}</li>
                </ul>
            </AppMessage>

            <template v-else>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="bg-surface-2 border border-line rounded-md p-3">
                        <p class="text-xs uppercase tracking-wider text-muted">{{ t("personal_finance.import.valid_rows") }}</p>
                        <p class="text-2xl font-mono text-emerald-400 mt-1">{{ preview.validCount }}</p>
                    </div>
                    <div class="bg-surface-2 border border-line rounded-md p-3">
                        <p class="text-xs uppercase tracking-wider text-muted">{{ t("personal_finance.import.invalid_rows") }}</p>
                        <p class="text-2xl font-mono mt-1" :class="preview.invalidCount > 0 ? 'text-rose-400' : 'text-muted'">{{ preview.invalidCount }}</p>
                    </div>
                    <div class="bg-surface-2 border border-line rounded-md p-3">
                        <p class="text-xs uppercase tracking-wider text-muted">{{ t("personal_finance.import.new_categories") }}</p>
                        <p class="text-2xl font-mono text-accent-400 mt-1">{{ preview.newCategoryNames.length }}</p>
                    </div>
                </div>

                <div v-if="preview.newCategoryNames.length" class="text-xs text-muted">
                    {{ t("personal_finance.import.new_categories_list", { list: preview.newCategoryNames.join(", ") }) }}
                </div>

                <div class="border border-line rounded-md overflow-hidden">
                    <div class="max-h-96 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-surface-2 text-xs uppercase tracking-wider text-muted sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left">#</th>
                                    <th class="px-3 py-2 text-left">{{ t("personal_finance.import.columns.date") }}</th>
                                    <th class="px-3 py-2 text-left">{{ t("personal_finance.import.columns.type") }}</th>
                                    <th class="px-3 py-2 text-right">{{ t("personal_finance.import.columns.amount") }}</th>
                                    <th class="px-3 py-2 text-left">{{ t("personal_finance.import.columns.category") }}</th>
                                    <th class="px-3 py-2 text-left">{{ t("personal_finance.import.columns.description") }}</th>
                                    <th class="px-3 py-2 text-left">{{ t("personal_finance.import.columns.status") }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <tr v-for="row in preview.rows" :key="row.rowNumber" :class="rowClasses(row)">
                                    <td class="px-3 py-2 text-muted font-mono">{{ row.rowNumber }}</td>
                                    <td class="px-3 py-2">{{ row.date ?? "—" }}</td>
                                    <td class="px-3 py-2">{{ row.type ?? "—" }}</td>
                                    <td class="px-3 py-2 text-right font-mono" :class="row.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">
                                        {{ row.amount ?? "—" }}
                                    </td>
                                    <td class="px-3 py-2 truncate">{{ row.categoryName ?? "—" }}</td>
                                    <td class="px-3 py-2 truncate">{{ row.description ?? "—" }}</td>
                                    <td class="px-3 py-2">
                                        <span v-if="row.valid" class="inline-flex items-center gap-1 text-emerald-400">
                                            <CheckCircle2 class="w-3.5 h-3.5" :stroke-width="2" />
                                            {{ t("personal_finance.import.status_valid") }}
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 text-rose-400" :title="row.errors.join(', ')">
                                            <AlertTriangle class="w-3.5 h-3.5" :stroke-width="2" />
                                            {{ row.errors.join(", ") }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-line">
                    <AppButton variant="primary" size="md" :loading="loading" :disabled="!canConfirm" v-on:click="confirmProcess">
                        <Play class="w-4 h-4" :stroke-width="2" />
                        {{ t("personal_finance.import.confirm_button", { count: preview.validCount }, preview.validCount) }}
                    </AppButton>
                </div>
            </template>
        </section>

        <!-- Step 3: done -->
        <section v-if="step === 'done' && report" class="bg-surface border border-line rounded-lg p-6 space-y-4">
            <div class="flex items-center gap-2 text-emerald-400">
                <CheckCircle2 class="w-5 h-5" :stroke-width="2" />
                <h3 class="text-sm font-medium uppercase tracking-wider">{{ t("personal_finance.import.step_done") }}</h3>
            </div>
            <p class="text-sm text-primary">{{ t("personal_finance.import.done_summary", { created: report.createdCount, skipped: report.skippedCount }) }}</p>
            <p v-if="report.categoriesCreated.length" class="text-sm text-muted">
                {{ t("personal_finance.import.new_categories_list", { list: report.categoriesCreated.join(", ") }) }}
            </p>
            <ul v-if="report.skippedRows.length" class="text-xs text-muted bg-surface-2 border border-line rounded-md p-3 max-h-48 overflow-y-auto space-y-1">
                <li v-for="(line, idx) in report.skippedRows" :key="`skip-${idx}`" class="font-mono">{{ line }}</li>
            </ul>
            <div class="flex justify-end pt-2 border-t border-line">
                <AppButton variant="ghost" size="md" v-on:click="reset">
                    <RotateCcw class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.import.new_import") }}
                </AppButton>
            </div>
        </section>
    </div>
</template>
