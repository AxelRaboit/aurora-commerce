<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { usePdfDocumentsForm, DOCUMENT_STATUS_BADGE } from "./composables/usePdfDocumentsForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppLoadMore from "@/shared/components/nav/AppLoadMore.vue";
import AppListItemButton from "@/shared/components/action/AppListItemButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import PdfCanvasEditor from "./components/PdfCanvasEditor.vue";
import SignaturePad from "./components/SignaturePad.vue";
import SignatureDisplay from "./components/SignatureDisplay.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import { Plus, Trash2, FileOutput, X, Download, FileText, ChevronLeft, Eye, Loader2, PenLine } from "lucide-vue-next";

const viewingDoc = ref(null);


const { t } = useI18n();
const { can } = usePrivileges();
const props = defineProps({
    documents: { type: Object, default: () => ({}) },
    templates: { type: Array, default: () => [] },
    search: { type: String, default: "" },
    generatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    listPath: { type: String, required: true },
    templateListPath: { type: String, required: true },
});

const { items, loading, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath, { initialSearch: props.search, initialData: props.documents },
);

const {
    showModal, step, openModal, backToPicker, goToSignature, backToEditor,
    pickerItems, pickerLoading, pickerLoadingMore, pickerHasMore,
    debouncedSearch, loadMorePicker, selectTemplate,
    editorTemplate, generateForm, generateErrors, generateLoading,
    fieldPositions, signatureData,
    submitGenerate,
    pendingDelete, deleteLoading, confirmDelete, doDelete,
} = usePdfDocumentsForm(props.generatePath, props.deletePath, props.templateListPath, reset);
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppSearchInput v-model="searchInput" :placeholder="t('backend.pdfform.documents.searchPlaceholder')" v-on:search="onSearch" />
            <template #actions>
                <AppButton
                    v-if="can('pdfform.documents.generate')"
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="openModal"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" /> {{ t("backend.pdfform.documents.add") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <div class="relative space-y-4">
            <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.pdfform.documents.reference") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("backend.pdfform.documents.template") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("backend.pdfform.documents.status") }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="doc in items" :key="doc.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-6 py-3">
                                <p class="font-medium text-primary font-mono">{{ doc.reference }}</p>
                                <p v-if="doc.label" class="text-xs text-muted">{{ doc.label }}</p>
                            </td>
                            <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ doc.templateName ?? "—" }}</td>
                            <td class="px-6 py-3 hidden lg:table-cell">
                                <AppBadge :color="DOCUMENT_STATUS_BADGE[doc.status]">{{ doc.statusLabel }}</AppBadge>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton v-if="doc.downloadUrl" color="sky" :title="t('backend.pdfform.documents.view')" v-on:click="viewingDoc = doc"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <a v-if="doc.downloadUrl" :href="doc.downloadUrl" target="_blank" rel="noopener">
                                        <AppIconButton color="sky" :title="t('backend.pdfform.documents.download')"><Download class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    </a>
                                    <AppIconButton v-if="can('pdfform.documents.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(doc)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items?.length">
                            <td :colspan="4"><AppNoData :message="t('backend.pdfform.documents.empty')" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />
            <AppLoader :active="loading" />
        </div>

        <!-- Modale unique : step 1 = picker, step 2 = éditeur -->
        <AppModal
            :show="showModal"
            :title="step === 1 ? t('backend.pdfform.documents.pickTemplate') : step === 3 ? t('backend.pdfform.documents.signatureTitle') : (editorTemplate?.name ?? '')"
            :icon="step === 1 ? FileText : step === 3 ? PenLine : FileOutput"
            :closeable="false"
            :max-width="step === 2 ? '7xl' : 'lg'"
            :scrollable="false"
            v-on:close="showModal = false"
        >
            <!-- Step 1 — Picker -->
            <div v-if="step === 1" class="space-y-3">
                <AppSearchInput :placeholder="t('backend.pdfform.templates.searchPlaceholder')" v-on:search="debouncedSearch" />
                <div class="min-h-48 max-h-96 overflow-y-auto scrollbar-thin rounded-lg border border-line">
                    <div v-if="pickerLoading" class="flex items-center justify-center py-12">
                        <Loader2 class="w-5 h-5 text-muted animate-spin" :stroke-width="2" />
                    </div>
                    <div v-else-if="!pickerItems.length" class="px-4">
                        <AppNoData :message="t('backend.pdfform.documents.noActiveTemplate')" />
                    </div>
                    <template v-else>
                        <AppListItemButton v-for="tpl in pickerItems" :key="tpl.id" v-on:click="selectTemplate(tpl)">
                            <template #icon><FileText class="w-4 h-4 text-accent shrink-0" :stroke-width="1.5" /></template>
                            {{ tpl.name }}
                            <template #meta>{{ tpl.description ?? "" }}</template>
                        </AppListItemButton>
                        <div v-if="pickerHasMore" class="px-3 py-2 border-t border-line/40">
                            <AppLoadMore :has-more="pickerHasMore" :loading="pickerLoadingMore" v-on:load="loadMorePicker" />
                        </div>
                    </template>
                </div>
            </div>

            <!-- Step 3 — Signature -->
            <div v-else-if="step === 3" class="space-y-4">
                <p class="text-sm text-secondary">{{ t("backend.pdfform.documents.signatureDesc") }}</p>
                <SignaturePad v-model="signatureData" />
            </div>

            <!-- Step 2 — Éditeur -->
            <div v-else class="flex gap-6 h-[70vh]">
                <div class="hidden lg:flex flex-col flex-1 min-w-0 overflow-hidden">
                    <PdfCanvasEditor
                        v-if="editorTemplate?.fileUrl"
                        :pdf-url="editorTemplate.fileUrl"
                        :field-positions="fieldPositions"
                        :field-values="generateForm.fieldValues"
                        class="flex-1 h-full"
                        v-on:update:field-values="(vals) => { generateForm.fieldValues = vals }"
                    />
                    <div v-else class="flex-1 flex items-center justify-center text-muted text-sm rounded-lg border border-line bg-surface-2/40">
                        {{ t("backend.pdfform.documents.noPreview") }}
                    </div>
                </div>
                <div class="flex flex-col w-full lg:w-80 shrink-0 overflow-y-auto scrollbar-thin space-y-4 pr-1">
                    <AppInput v-model="generateForm.label" :label="t('backend.pdfform.documents.label')" :placeholder="t('backend.pdfform.documents.labelPlaceholder')" />
                    <p v-if="generateErrors.templateId" class="text-sm text-rose-500">{{ generateErrors.templateId }}</p>
                    <div v-if="editorTemplate?.fields?.length" class="space-y-3">
                        <p class="text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.pdfform.documents.fieldValues") }}</p>
                        <div v-for="field in editorTemplate.fields" :key="field.id">
                            <AppInput v-model="generateForm.fieldValues[field.pdfFieldName]" :label="field.label" :placeholder="field.defaultValue ?? field.pdfFieldName" />
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <AppModalFooter>
                    <template v-if="step === 1">
                        <AppButton variant="ghost" size="md" v-on:click="showModal = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    </template>
                    <template v-else-if="step === 3">
                        <AppButton variant="ghost" size="md" v-on:click="backToEditor"><ChevronLeft class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.back") }}</AppButton>
                        <AppButton variant="ghost" size="md" v-on:click="showModal = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                        <AppButton
                            variant="primary"
                            size="md"
                            :loading="generateLoading"
                            :disabled="!signatureData"
                            v-on:click="submitGenerate"
                        >
                            <FileOutput class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.pdfform.documents.generate") }}
                        </AppButton>
                    </template>
                    <template v-else>
                        <AppButton variant="ghost" size="md" v-on:click="backToPicker"><ChevronLeft class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.back") }}</AppButton>
                        <AppButton variant="ghost" size="md" v-on:click="showModal = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                        <AppButton v-if="editorTemplate?.requiresSignature" variant="primary" size="md" v-on:click="goToSignature">
                            <PenLine class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.pdfform.documents.signatureNext") }}
                        </AppButton>
                        <AppButton
                            v-else
                            variant="primary"
                            size="md"
                            :loading="generateLoading"
                            v-on:click="submitGenerate"
                        >
                            <FileOutput class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.pdfform.documents.generate") }}
                        </AppButton>
                    </template>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- View modal -->
        <AppModal
            :show="!!viewingDoc"
            :title="viewingDoc?.label ?? viewingDoc?.reference ?? ''"
            :icon="Eye"
            :closeable="false"
            max-width="7xl"
            :scrollable="false"
            v-on:close="viewingDoc = null"
        >
            <div class="h-[75vh] rounded-lg border border-line overflow-hidden bg-surface-2/40">
                <PdfCanvasEditor
                    v-if="viewingDoc?.downloadUrl"
                    :pdf-url="viewingDoc.downloadUrl"
                    :field-positions="{}"
                    :field-values="{}"
                    class="w-full h-full"
                />
            </div>
            <SignatureDisplay v-if="viewingDoc?.fieldValues?.__signature__" :src="viewingDoc.fieldValues.__signature__" />
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="viewingDoc = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.close") }}</AppButton>
                    <a :href="viewingDoc?.downloadUrl" target="_blank" rel="noopener">
                        <AppButton variant="secondary" size="md"><Download class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.pdfform.documents.download") }}</AppButton>
                    </a>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Delete modal -->
        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">{{ t("backend.pdfform.documents.deleteConfirm", { reference: pendingDelete?.reference ?? "" }) }}</p>
            <p class="text-sm text-secondary">{{ t("backend.pdfform.documents.deleteWarning") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
