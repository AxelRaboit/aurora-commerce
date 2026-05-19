<script setup>
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { usePdfTemplatesForm, TEMPLATE_STATUS_BADGE } from "./composables/usePdfTemplatesForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppCheckbox from "@/shared/components/form/toggle/AppCheckbox.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import MediaPickerModal from "@media/backend/media/MediaPickerModal.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import { Plus, Pencil, Trash2, Save, FileText, Paperclip, X, ScanSearch, Settings } from "lucide-vue-next";

const { t } = useI18n();
const { can } = usePrivileges();
const props = defineProps({
    templates: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    detectFieldsPath: { type: String, required: true },
    updateFieldPath: { type: String, required: true },
    listPath: { type: String, required: true },
    mediaPickerPath: { type: String, default: "" },
});

const { items, loading, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath, { initialSearch: props.search, initialData: props.templates },
);

const {
    statusOptions,
    showCreate, newTemplate, showMediaPickerCreate, createErrors, createLoading, openCreate, onFilePickedCreate, submitCreate,
    showEdit, editingTemplate, editForm, showMediaPickerEdit, editErrors, editLoading, openEdit, onFilePickedEdit, submitEdit,
    pendingDelete, deleteLoading, confirmDelete, doDelete,
    showFields, fieldsTemplate, detectLoading, openFields, detectFields,
    fieldTypeOptions, editingField, fieldForm, showEditField, fieldErrors, fieldEditLoading, openEditField, submitFieldEdit,
} = usePdfTemplatesForm(props.createPath, props.updatePath, props.deletePath, props.detectFieldsPath, props.updateFieldPath, reset);
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppSearchInput v-model="searchInput" :placeholder="t('backend.pdfform.templates.searchPlaceholder')" v-on:search="onSearch" />
            <template #actions>
                <AppButton
                    v-if="can('pdfform.templates.create')"
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="openCreate"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" /> {{ t("backend.pdfform.templates.add") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <div class="relative space-y-4">
            <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.pdfform.templates.name") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("backend.pdfform.templates.status") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("backend.pdfform.templates.fieldCount") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("backend.pdfform.templates.file") }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="tpl in items" :key="tpl.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-6 py-3">
                                <p class="font-medium text-primary">{{ tpl.name }}</p>
                                <p v-if="tpl.description" class="text-xs text-muted truncate max-w-xs">{{ tpl.description }}</p>
                            </td>
                            <td class="px-6 py-3 hidden md:table-cell">
                                <AppBadge :color="TEMPLATE_STATUS_BADGE[tpl.status]">{{ tpl.statusLabel }}</AppBadge>
                            </td>
                            <td class="px-6 py-3 hidden lg:table-cell text-secondary">{{ tpl.fieldCount }}</td>
                            <td class="px-6 py-3 hidden lg:table-cell">
                                <span v-if="tpl.fileName" class="flex items-center gap-1 text-xs text-muted"><Paperclip class="w-3 h-3" :stroke-width="2" /> {{ tpl.fileName }}</span>
                                <span v-else class="text-muted text-xs">—</span>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton v-if="can('pdfform.templates.edit')" color="sky" :title="t('backend.pdfform.templates.detectFields')" v-on:click="openFields(tpl)"><ScanSearch class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton v-if="can('pdfform.templates.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(tpl)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton v-if="can('pdfform.templates.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(tpl)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items?.length">
                            <td :colspan="5"><AppNoData :message="t('backend.pdfform.templates.empty')" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />
            <AppLoader :active="loading" />
        </div>

        <!-- Create modal -->
        <AppModal
            :show="showCreate"
            :title="t('backend.pdfform.templates.create')"
            :icon="FileText"
            :closeable="false"
            v-on:close="showCreate = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="newTemplate.name"
                    :label="t('backend.pdfform.templates.name')"
                    :placeholder="t('backend.pdfform.templates.namePlaceholder')"
                    :error="createErrors.name"
                    required
                />
                <AppInput v-model="newTemplate.description" :label="t('backend.pdfform.templates.description')" :placeholder="t('backend.pdfform.templates.descriptionPlaceholder')" />
                <AppMultiselect
                    v-model="newTemplate.status"
                    :label="t('backend.pdfform.templates.status')"
                    :options="statusOptions"
                    :allow-empty="false"
                    :searchable="false"
                />
                <div class="flex items-center gap-3">
                    <AppButton variant="ghost" size="sm" type="button" v-on:click="showMediaPickerCreate = true">
                        <Paperclip class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.pdfform.templates.chooseFile") }}
                    </AppButton>
                    <span v-if="newTemplate.fileName" class="text-sm text-muted flex items-center gap-1"><FileText class="w-4 h-4" :stroke-width="2" /> {{ newTemplate.fileName }}</span>
                </div>
                <div class="space-y-0.5">
                    <AppCheckbox v-model="newTemplate.flattenOnGenerate" :label="t('backend.pdfform.templates.flattenOnGenerate')" />
                    <p class="text-xs text-muted pl-6">{{ t("backend.pdfform.templates.flattenOnGenerateHelp") }}</p>
                    <AppCheckbox v-model="newTemplate.requiresSignature" :label="t('backend.pdfform.templates.requiresSignature')" />
                    <p class="text-xs text-muted pl-6">{{ t("backend.pdfform.templates.requiresSignatureHelp") }}</p>
                    <AppCheckbox v-model="newTemplate.autoDetectFields" :label="t('backend.pdfform.templates.autoDetectFields')" :disabled="!newTemplate.fileId" />
                    <p class="text-xs text-muted pl-6">{{ newTemplate.fileId ? t("backend.pdfform.templates.autoDetectFieldsHelp") : t("backend.pdfform.templates.autoDetectFieldsNeedFile") }}</p>
                </div>
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="createLoading"
                        v-on:click="submitCreate"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Edit modal -->
        <AppModal
            :show="showEdit"
            :title="t('backend.pdfform.templates.edit', { name: editingTemplate?.name ?? '' })"
            :icon="Pencil"
            :closeable="false"
            v-on:close="showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('backend.pdfform.templates.name')"
                    :placeholder="t('backend.pdfform.templates.namePlaceholder')"
                    :error="editErrors.name"
                    required
                />
                <AppInput v-model="editForm.description" :label="t('backend.pdfform.templates.description')" :placeholder="t('backend.pdfform.templates.descriptionPlaceholder')" />
                <AppMultiselect
                    v-model="editForm.status"
                    :label="t('backend.pdfform.templates.status')"
                    :options="statusOptions"
                    :allow-empty="false"
                    :searchable="false"
                />
                <div class="flex items-center gap-3">
                    <AppButton variant="ghost" size="sm" type="button" v-on:click="showMediaPickerEdit = true">
                        <Paperclip class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.pdfform.templates.chooseFile") }}
                    </AppButton>
                    <span v-if="editForm.fileName" class="text-sm text-muted flex items-center gap-1"><FileText class="w-4 h-4" :stroke-width="2" /> {{ editForm.fileName }}</span>
                </div>
                <div class="space-y-0.5">
                    <AppCheckbox v-model="editForm.flattenOnGenerate" :label="t('backend.pdfform.templates.flattenOnGenerate')" />
                    <p class="text-xs text-muted pl-6">{{ t("backend.pdfform.templates.flattenOnGenerateHelp") }}</p>
                    <AppCheckbox v-model="editForm.requiresSignature" :label="t('backend.pdfform.templates.requiresSignature')" />
                    <p class="text-xs text-muted pl-6">{{ t("backend.pdfform.templates.requiresSignatureHelp") }}</p>
                </div>
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="editLoading"
                        v-on:click="submitEdit"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}
                    </AppButton>
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
            <p class="text-sm text-primary">{{ t("backend.pdfform.templates.deleteConfirm", { name: pendingDelete?.name ?? "" }) }}</p>
            <p class="text-sm text-secondary">{{ t("backend.pdfform.templates.deleteWarning") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Fields modal -->
        <AppModal
            :show="showFields"
            :title="fieldsTemplate?.name ?? ''"
            :icon="ScanSearch"
            :closeable="false"
            max-width="2xl"
            v-on:close="showFields = false"
        >
            <div v-if="fieldsTemplate?.fields?.length" class="border border-line rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.pdfform.fields.label") }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-muted hidden sm:table-cell">{{ t("backend.pdfform.fields.fieldType") }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("backend.pdfform.fields.pdfFieldName") }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="field in fieldsTemplate.fields" :key="field.id" class="hover:bg-surface-2/40">
                            <td class="px-4 py-2 font-medium text-primary">{{ field.label }}</td>
                            <td class="px-4 py-2 text-secondary hidden sm:table-cell">{{ field.fieldTypeLabel }}</td>
                            <td class="px-4 py-2 text-muted font-mono text-xs hidden md:table-cell">{{ field.pdfFieldName }}</td>
                            <td class="px-4 py-2 text-right">
                                <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEditField(field)"><Settings class="w-3.5 h-3.5" :stroke-width="2" /></AppIconButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-sm text-muted">{{ t("backend.pdfform.templates.detectFieldsEmpty") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showFields = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.close") }}</AppButton>
                    <AppButton variant="secondary" size="md" :loading="detectLoading" v-on:click="detectFields">
                        <ScanSearch class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.pdfform.templates.detectFields") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Edit field modal -->
        <AppModal
            :show="showEditField"
            :title="t('backend.pdfform.fields.editField', { label: editingField?.label ?? '' })"
            :icon="Settings"
            :closeable="false"
            v-on:close="showEditField = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitFieldEdit">
                <AppInput v-model="fieldForm.label" :label="t('backend.pdfform.fields.label')" :error="fieldErrors.label" required />
                <AppInput v-model="fieldForm.pdfFieldName" :label="t('backend.pdfform.fields.pdfFieldName')" :error="fieldErrors.pdfFieldName" required />
                <AppMultiselect
                    v-model="fieldForm.fieldType"
                    :label="t('backend.pdfform.fields.fieldType')"
                    :options="fieldTypeOptions"
                    :allow-empty="false"
                    :searchable="false"
                />
                <AppInput v-model="fieldForm.mappingKey" :label="t('backend.pdfform.fields.mappingKey')" :placeholder="t('backend.pdfform.fields.mappingKeyPlaceholder')" />
                <AppInput v-model="fieldForm.defaultValue" :label="t('backend.pdfform.fields.defaultValue')" :placeholder="t('backend.pdfform.fields.defaultValuePlaceholder')" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showEditField = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" :loading="fieldEditLoading" v-on:click="submitFieldEdit"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Media pickers -->
        <MediaPickerModal :show="showMediaPickerCreate" :list-path="mediaPickerPath" v-on:close="showMediaPickerCreate = false" v-on:select="onFilePickedCreate" />
        <MediaPickerModal :show="showMediaPickerEdit" :list-path="mediaPickerPath" v-on:close="showMediaPickerEdit = false" v-on:select="onFilePickedEdit" />
    </div>
</template>
