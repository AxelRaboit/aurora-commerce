<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { VueDraggable } from "vue-draggable-plus";
import { FormFieldType } from "@editorial/shared/enums/formFieldType.js";
import { useFormsList } from "@editorial/backend/forms/composables/useFormsList.js";
import { useFormEditor } from "@editorial/backend/forms/composables/useFormEditor.js";
import { useFormFields } from "@editorial/backend/forms/composables/useFormFields.js";
import { useFormSubmissions } from "@editorial/backend/forms/composables/useFormSubmissions.js";
import {
    ClipboardList,
    Plus,
    Trash2,
    GripVertical,
    Pencil,
    Download,
    Eye,
    Settings,
    Layers,
    Inbox,
    Save,
    Webhook,
    GitBranch,
    Users,
    X,
} from "lucide-vue-next";
import FormPreviewModal from "@editorial/backend/forms/FormPreviewModal.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppCheckbox from "@/shared/components/form/AppCheckbox.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import { slugify } from "@/shared/utils/format/slugify.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    locales: { type: Array, default: () => ["fr"] },
    listPath: { type: String, required: true },
    getPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    fieldCreatePath: { type: String, required: true },
    fieldUpdatePath: { type: String, required: true },
    fieldDeletePath: { type: String, required: true },
    fieldReorderPath: { type: String, required: true },
    submissionsPath: { type: String, required: true },
    exportPath: { type: String, required: true },
});

const { forms, loading, page, totalPages, total, fetchForms, goToPage } = useFormsList(props.listPath);

const { selectedForm, editingForm, formErrors, saving, activeTab, activeLocale, showDeleteConfirm, deleting, slugLocked, sharedSlug, isCreating, emptyForm, defaultLocale, formTitle, isLocaleFilled, localeFieldError, jsonRequest, startCreate, selectForm, onTitleInput, onSlugInput, onSharedSlugToggle, saveForm, confirmDelete } =
    useFormEditor(props, fetchForms);

const tabs = computed(() => {
    if (isCreating.value) return [{ key: "settings", label: t("backend.forms.tabs.settings"), icon: Settings }];
    return [
        { key: "settings", label: t("backend.forms.tabs.settings"), icon: Settings },
        { key: "fields",   label: t("backend.forms.tabs.fields"),   icon: Layers },
        { key: "submissions", label: t("backend.forms.tabs.submissions"), icon: Inbox },
    ];
});

const { showFieldModal, editingField, editingFieldId, fieldOptionsText, fieldErrors, fieldSaving, fieldActiveLocale, FIELD_TYPES, OPERATORS, fieldHasOptions, fieldTypeLabel, fieldLabel, openAddField, openEditField, submitField, pendingDeleteField, deleteFieldLoading, confirmDeleteField, doDeleteField, onFieldsReordered } =
    useFormFields(props, selectedForm, editingForm, jsonRequest);

// Steps helpers
function addStep() {
    const step = Object.fromEntries(props.locales.map((l) => [l, ""]));
    editingForm.value.steps = [...(editingForm.value.steps ?? []), step];
}
function removeStep(index) {
    editingForm.value.steps = editingForm.value.steps.filter((_, i) => i !== index);
}

// Conditions helpers
const otherFields = computed(() =>
    (editingForm.value.fields ?? []).filter((f) => f.id !== editingFieldId.value),
);
function addCondition() {
    const firstField = otherFields.value[0];
    editingField.value.conditions = [
        ...(editingField.value.conditions ?? []),
        { fieldId: firstField?.id ?? null, operator: "eq", value: "" },
    ];
}
function removeCondition(index) {
    editingField.value.conditions = editingField.value.conditions.filter((_, i) => i !== index);
}

const { submissionFields, viewingSubmission, submissions, submissionsLoading, submissionsPage, submissionsTotalPages, submissionsTotal, fetchSubmissions, goToSubmissionsPage, resetSubmissions, exportCsv, submissionValue, onTabChange: onTabChangeBase } =
    useFormSubmissions(props.submissionsPath, props.exportPath, selectedForm, activeLocale);

function onTabChange(tab) { onTabChangeBase(tab, activeTab); }

const showPreview = ref(false);
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]">
        <div class="lg:w-72 shrink-0 flex flex-col gap-3">
            <AppButton
                v-if="can('editorial.forms.create')"
                variant="primary"
                size="md"
                class="w-full justify-center"
                v-on:click="startCreate"
            >
                <Plus class="w-4 h-4 shrink-0" :stroke-width="2" />
                {{ t("backend.forms.create") }}
            </AppButton>

            <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
                <AppNoData v-if="!loading && !forms.length" :message="t('backend.forms.empty')" />
                <ul v-else class="divide-y divide-line/60">
                    <li
                        v-for="form in forms"
                        :key="form.id"
                        class="px-4 py-3 cursor-pointer hover:bg-surface-2/50 transition-colors flex items-start gap-2"
                        :class="selectedForm?.id === form.id ? 'bg-accent-600/10' : ''"
                        v-on:click="selectForm(form)"
                    >
                        <ClipboardList class="w-4 h-4 shrink-0 mt-0.5" :class="selectedForm?.id === form.id ? 'text-accent-400' : 'text-muted'" :stroke-width="2" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium truncate" :class="selectedForm?.id === form.id ? 'text-accent-400' : 'text-primary'">{{ formTitle(form) || "—" }}</p>
                            <p class="text-xs text-muted">{{ form.submissionCount }} {{ t("backend.forms.submissions_count") }}</p>
                        </div>
                        <AppBadge v-if="!form.active" color="gray" class="shrink-0">{{ t("backend.forms.inactive") }}</AppBadge>
                    </li>
                </ul>
            </div>

            <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />
        </div>

        <div v-if="selectedForm || isCreating" class="flex-1 min-w-0 min-h-0 bg-surface border border-line/60 rounded-xl overflow-hidden flex flex-col">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-line/60">
                <div class="flex items-center gap-2 min-w-0">
                    <ClipboardList class="w-5 h-5 shrink-0 text-accent-400" :stroke-width="2" />
                    <h2 class="text-base font-semibold text-primary truncate">
                        {{ isCreating ? t("backend.forms.newForm") : (formTitle(selectedForm) || "—") }}
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <AppButton
                        v-if="!isCreating && can('editorial.forms.edit')"
                        variant="danger"
                        size="md"
                        v-on:click="showDeleteConfirm = true"
                    >
                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                    <AppButton
                        variant="secondary"
                        size="md"
                        v-on:click="showPreview = true"
                    >
                        <Eye class="w-4 h-4" :stroke-width="2" />
                        {{ t("backend.forms.preview") }}
                    </AppButton>
                    <AppButton
                        v-if="activeTab === 'settings'"
                        variant="primary"
                        size="md"
                        :disabled="saving"
                        v-on:click="saveForm"
                    >
                        <Save v-if="!saving" class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ saving ? t("shared.common.loading") : t("shared.common.save") }}
                    </AppButton>
                </div>
            </div>

            <div class="flex gap-1 px-5 pt-3 border-b border-line/60">
                <AppTab
                    v-for="tab in tabs"
                    :key="tab.key"
                    variant="underline"
                    size="sm"
                    :active="activeTab === tab.key"
                    v-on:click="onTabChange(tab.key)"
                >
                    <component :is="tab.icon" class="w-4 h-4" :stroke-width="2" />
                    {{ tab.label }}
                </AppTab>
            </div>

            <div v-if="activeTab === 'settings'" class="p-5 space-y-4 overflow-y-auto flex-1">
                <div v-if="locales.length > 1" class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                    <div class="flex gap-1">
                        <AppTab
                            v-for="locale in locales"
                            :key="locale"
                            size="xs"
                            :active="activeLocale === locale"
                            active-class="bg-accent-600 text-white"
                            inactive-class="bg-surface-2 text-secondary hover:bg-surface-3"
                            v-on:click="activeLocale = locale"
                        >
                            {{ locale.toUpperCase() }}
                            <span
                                class="inline-block w-1.5 h-1.5 rounded-full"
                                :class="isLocaleFilled(locale) ? 'bg-emerald-400' : 'bg-muted/40'"
                                :title="isLocaleFilled(locale) ? t('backend.forms.localeFilled') : t('backend.forms.localeEmpty')"
                            />
                        </AppTab>
                    </div>
                    <p class="text-xs text-muted">{{ t("backend.forms.localesOptional") }}</p>
                </div>

                <div :class="sharedSlug ? '' : 'grid grid-cols-1 sm:grid-cols-2 gap-4'">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.title") }}</label>
                        <input
                            v-model="editingForm.translations[activeLocale].title"
                            type="text"
                            class="w-full px-3 py-2 rounded-lg bg-surface-2 border text-sm text-primary placeholder-muted focus:outline-none focus:ring-1 focus:ring-accent-500"
                            :class="localeFieldError(formErrors, activeLocale, 'title') ? 'border-rose-500' : 'border-line/60'"
                            :placeholder="t('backend.forms.titlePlaceholder')"
                            v-on:input="onTitleInput"
                        >
                        <p v-if="localeFieldError(formErrors, activeLocale, 'title')" class="text-xs text-rose-400">{{ localeFieldError(formErrors, activeLocale, 'title') }}</p>
                    </div>
                    <div v-if="!sharedSlug" class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.slug") }}</label>
                        <input
                            v-model="editingForm.translations[activeLocale].slug"
                            type="text"
                            class="w-full px-3 py-2 rounded-lg bg-surface-2 border text-sm text-primary placeholder-muted focus:outline-none focus:ring-1 focus:ring-accent-500 font-mono"
                            :class="localeFieldError(formErrors, activeLocale, 'slug') ? 'border-rose-500' : 'border-line/60'"
                            :placeholder="t('backend.forms.slugPlaceholder')"
                            v-on:input="onSlugInput"
                        >
                        <p v-if="localeFieldError(formErrors, activeLocale, 'slug')" class="text-xs text-rose-400">{{ localeFieldError(formErrors, activeLocale, 'slug') }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.description") }}</label>
                    <textarea
                        v-model="editingForm.translations[activeLocale].description"
                        rows="3"
                        class="w-full px-3 py-2 rounded-lg bg-surface-2 border border-line/60 text-sm text-primary placeholder-muted focus:outline-none focus:ring-1 focus:ring-accent-500 resize-none"
                        :placeholder="t('backend.forms.descriptionPlaceholder')"
                    />
                </div>

                <hr class="border-line/40">

                <div class="space-y-2">
                    <div v-if="locales.length > 1" class="flex items-center gap-2">
                        <input
                            id="shared-slug"
                            v-model="sharedSlug"
                            type="checkbox"
                            class="rounded border-line text-accent-600 focus:ring-accent-500"
                            v-on:change="onSharedSlugToggle"
                        >
                        <label for="shared-slug" class="text-sm text-primary cursor-pointer">{{ t("backend.forms.sharedSlug") }}</label>
                    </div>

                    <div v-if="sharedSlug" class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.slug") }}</label>
                        <input
                            v-model="editingForm.translations[activeLocale].slug"
                            type="text"
                            class="w-full px-3 py-2 rounded-lg bg-surface-2 border text-sm text-primary placeholder-muted focus:outline-none focus:ring-1 focus:ring-accent-500 font-mono"
                            :class="localeFieldError(formErrors, activeLocale, 'slug') ? 'border-rose-500' : 'border-line/60'"
                            :placeholder="t('backend.forms.slugPlaceholder')"
                            v-on:input="onSlugInput"
                        >
                        <p v-if="localeFieldError(formErrors, activeLocale, 'slug')" class="text-xs text-rose-400">{{ localeFieldError(formErrors, activeLocale, 'slug') }}</p>
                    </div>
                </div>

                <hr class="border-line/40">

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.notifyEmail") }}</label>
                    <input
                        v-model="editingForm.notifyEmail"
                        type="email"
                        class="w-full px-3 py-2 rounded-lg bg-surface-2 border border-line/60 text-sm text-primary placeholder-muted focus:outline-none focus:ring-1 focus:ring-accent-500"
                        :placeholder="t('backend.forms.notifyEmailPlaceholder')"
                    >
                    <p class="text-xs text-muted">{{ t("backend.forms.notifyEmailHint") }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center gap-1.5">
                        <Webhook class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                        <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.webhookUrl") }}</label>
                    </div>
                    <input
                        v-model="editingForm.webhookUrl"
                        type="url"
                        class="w-full px-3 py-2 rounded-lg bg-surface-2 border border-line/60 text-sm text-primary placeholder-muted font-mono focus:outline-none focus:ring-1 focus:ring-accent-500"
                        :placeholder="t('backend.forms.webhookUrlPlaceholder')"
                    >
                    <p class="text-xs text-muted">{{ t("backend.forms.webhookUrlHint") }}</p>
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors"
                            :class="editingForm.crmSync ? 'bg-accent-600' : 'bg-surface-3'"
                            v-on:click="editingForm.crmSync = !editingForm.crmSync"
                        >
                            <span class="inline-block h-4 w-4 rounded-full bg-white transition-transform shadow-sm" :class="editingForm.crmSync ? 'translate-x-4' : 'translate-x-0.5'" />
                        </button>
                        <div>
                            <label class="text-sm text-primary cursor-pointer flex items-center gap-1.5" v-on:click="editingForm.crmSync = !editingForm.crmSync">
                                <Users class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                                {{ t("backend.forms.crmSync") }}
                            </label>
                            <p class="text-xs text-muted">{{ t("backend.forms.crmSyncHint") }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <GitBranch class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                            <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.steps") }}</label>
                        </div>
                        <button class="text-xs text-accent-400 hover:text-accent-300 flex items-center gap-1" v-on:click="addStep">
                            <Plus class="w-3 h-3" :stroke-width="2" />
                            {{ t("backend.forms.addStep") }}
                        </button>
                    </div>
                    <p v-if="!editingForm.steps?.length" class="text-xs text-muted">{{ t("backend.forms.stepsEmpty") }}</p>
                    <div v-for="(step, i) in editingForm.steps" :key="i" class="flex items-center gap-2">
                        <span class="text-xs text-muted w-5 shrink-0 text-center">{{ i + 1 }}</span>
                        <input
                            v-model="editingForm.steps[i][activeLocale]"
                            type="text"
                            class="flex-1 px-2.5 py-1.5 rounded-lg bg-surface-2 border border-line/60 text-sm text-primary focus:outline-none focus:ring-1 focus:ring-accent-500"
                            :placeholder="`${t('backend.forms.stepLabel')} ${i + 1}`"
                        >
                        <button class="text-muted hover:text-rose-400 transition-colors shrink-0" v-on:click="removeStep(i)">
                            <X class="w-4 h-4" :stroke-width="2" />
                        </button>
                    </div>
                    <p v-if="editingForm.steps?.length" class="text-xs text-muted">{{ t("backend.forms.stepsHint") }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors"
                        :class="editingForm.active ? 'bg-accent-600' : 'bg-surface-3'"
                        v-on:click="editingForm.active = !editingForm.active"
                    >
                        <span class="inline-block h-4 w-4 rounded-full bg-white transition-transform shadow-sm" :class="editingForm.active ? 'translate-x-4' : 'translate-x-0.5'" />
                    </button>
                    <label class="text-sm text-primary cursor-pointer" v-on:click="editingForm.active = !editingForm.active">{{ t("backend.forms.active") }}</label>
                </div>
            </div>

            <div v-if="activeTab === 'fields'" class="p-5 flex flex-col gap-4 overflow-y-auto flex-1">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-secondary">{{ t("backend.forms.fieldsHint") }}</p>
                    <AppButton v-if="can('editorial.forms.edit')" variant="secondary" size="md" v-on:click="openAddField">
                        <Plus class="w-4 h-4" :stroke-width="2" />
                        {{ t("backend.forms.addField") }}
                    </AppButton>
                </div>

                <div v-if="editingForm.fields.length === 0" class="py-10 text-center text-sm text-muted">
                    {{ t("backend.forms.fieldsEmpty") }}
                </div>

                <VueDraggable
                    v-else
                    v-model="editingForm.fields"
                    handle=".drag-handle"
                    :animation="150"
                    class="space-y-2"
                    v-on:end="onFieldsReordered"
                >
                    <div
                        v-for="field in editingForm.fields"
                        :key="field.id"
                        class="flex items-center gap-3 px-4 py-3 bg-surface hover:bg-surface-2/50 border border-line/60 rounded-lg transition-colors"
                    >
                        <GripVertical class="drag-handle w-4 h-4 text-muted cursor-grab active:cursor-grabbing shrink-0" :stroke-width="2" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium text-primary">{{ fieldLabel(field) || "—" }}</span>
                                <AppBadge v-if="field.required" color="amber">{{ t("backend.forms.required") }}</AppBadge>
                                <AppBadge color="accent">{{ fieldTypeLabel(field.type) }}</AppBadge>
                            </div>
                        </div>
                        <AppIconButton v-if="can('editorial.forms.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEditField(field)">
                            <Pencil class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton v-if="can('editorial.forms.edit')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDeleteField(field)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </VueDraggable>
            </div>

            <div v-if="activeTab === 'submissions'" class="flex flex-col gap-4 overflow-y-auto flex-1">
                <div class="flex items-center justify-between px-5 pt-5">
                    <p class="text-sm text-secondary">{{ submissionsTotal }} {{ t("backend.forms.submissionsCount") }}</p>
                    <AppButton variant="secondary" size="md" :disabled="!submissions.length" v-on:click="exportCsv">
                        <Download class="w-4 h-4" :stroke-width="2" />
                        {{ t("backend.forms.exportCsv") }}
                    </AppButton>
                </div>

                <div class="bg-surface border-t border-line/60 overflow-hidden">
                    <AppNoData v-if="!submissionsLoading && !submissions.length" :message="t('backend.forms.submissionsEmpty')" />
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-surface-2/50 border-b border-line/40">
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted whitespace-nowrap">{{ t("backend.forms.submittedAt") }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted whitespace-nowrap">{{ t("backend.forms.locale") }}</th>
                                    <th v-for="field in submissionFields" :key="field.id" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted whitespace-nowrap max-w-xs">{{ fieldLabel(field) }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.edit") }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/40">
                                <tr v-for="submission in submissions" :key="submission.id" class="group hover:bg-surface-2/40 transition-colors">
                                    <td class="px-4 py-3 text-xs text-muted whitespace-nowrap">{{ formatDateTime(submission.submittedAt) }}</td>
                                    <td class="px-4 py-3 text-xs text-muted whitespace-nowrap uppercase">{{ submission.locale }}</td>
                                    <td v-for="field in submissionFields" :key="field.id" class="px-4 py-3 text-sm text-secondary max-w-xs truncate">
                                        {{ submissionValue(submission, field) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <AppIconButton color="accent" :title="t('backend.forms.viewSubmission')" v-on:click="viewingSubmission = submission">
                                            <Pencil class="w-4 h-4" :stroke-width="2" />
                                        </AppIconButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-5 pb-5">
                    <AppPagination :page="submissionsPage" :total-pages="submissionsTotalPages" v-on:change="goToSubmissionsPage" />
                </div>
            </div>
        </div>

        <div v-else class="flex-1 flex items-center justify-center text-sm text-muted bg-surface border border-line/60 rounded-xl">
            {{ t("backend.forms.selectOrCreate") }}
        </div>
    </div>

    <AppModal
        :show="showFieldModal"
        max-width="sm"
        :title="editingFieldId !== null ? t('backend.forms.editField') : t('backend.forms.addFieldTitle')"
        :icon="editingFieldId !== null ? Pencil : ClipboardList"
        :closeable="false"
        v-on:close="showFieldModal = false"
    >
        <div class="space-y-4">
            <AppSelect v-model="editingField.type" :label="t('backend.forms.fieldType')">
                <option v-for="ft in FIELD_TYPES" :key="ft.value" :value="ft.value">{{ ft.label }}</option>
            </AppSelect>

            <AppCheckbox v-model="editingField.required" :label="t('backend.forms.fieldRequired')" />

            <AppSelect
                v-if="editingForm.steps?.length"
                v-model="editingField.step"
                :label="t('backend.forms.fieldStep')"
            >
                <option :value="null">{{ t('backend.forms.fieldStepNone') }}</option>
                <option v-for="(step, i) in editingForm.steps" :key="i" :value="i">
                    {{ step[activeLocale] || step[props.locales[0]] || `${t('backend.forms.stepLabel')} ${i + 1}` }}
                </option>
            </AppSelect>

            <div v-if="otherFields.length" class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.conditions") }}</label>
                    <button class="text-xs text-accent-400 hover:text-accent-300 flex items-center gap-1" v-on:click="addCondition">
                        <Plus class="w-3 h-3" :stroke-width="2" /> {{ t("backend.forms.addCondition") }}
                    </button>
                </div>
                <div v-if="editingField.conditions?.length > 1" class="flex items-center gap-2">
                    <span class="text-xs text-muted">{{ t("backend.forms.conditionsLogicLabel") }}</span>
                    <button
                        v-for="logic in ['and', 'or']"
                        :key="logic"
                        class="px-2 py-0.5 text-xs rounded-md border transition-colors"
                        :class="editingField.conditionsLogic === logic ? 'bg-accent-600 border-accent-600 text-white' : 'border-line text-secondary hover:border-accent-400'"
                        v-on:click="editingField.conditionsLogic = logic"
                    >{{ t(`backend.forms.conditionsLogic.${logic}`) }}</button>
                </div>
                <div v-for="(condition, i) in editingField.conditions" :key="i" class="flex items-start gap-1.5 flex-wrap">
                    <select
                        v-model="condition.fieldId"
                        class="flex-1 min-w-28 px-2 py-1.5 rounded-lg bg-surface-2 border border-line/60 text-xs text-primary focus:outline-none"
                    >
                        <option v-for="f in otherFields" :key="f.id" :value="f.id">{{ fieldLabel(f) || f.id }}</option>
                    </select>
                    <select
                        v-model="condition.operator"
                        class="w-28 px-2 py-1.5 rounded-lg bg-surface-2 border border-line/60 text-xs text-primary focus:outline-none"
                    >
                        <option v-for="op in OPERATORS" :key="op.value" :value="op.value">{{ op.label }}</option>
                    </select>
                    <input
                        v-if="!['empty', 'not_empty'].includes(condition.operator)"
                        v-model="condition.value"
                        type="text"
                        class="flex-1 min-w-20 px-2 py-1.5 rounded-lg bg-surface-2 border border-line/60 text-xs text-primary focus:outline-none"
                        :placeholder="t('backend.forms.conditionValue')"
                    >
                    <button class="text-muted hover:text-rose-400 transition-colors mt-1.5" v-on:click="removeCondition(i)">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                    </button>
                </div>
                <p v-if="!editingField.conditions?.length" class="text-xs text-muted">{{ t("backend.forms.conditionsEmpty") }}</p>
            </div>

            <hr class="border-line/40">

            <div v-if="locales.length > 1" class="flex gap-1">
                <AppTab
                    v-for="locale in locales"
                    :key="locale"
                    size="xs"
                    :active="fieldActiveLocale === locale"
                    active-class="bg-accent-600 text-white"
                    inactive-class="bg-surface-2 text-secondary hover:bg-surface-3"
                    v-on:click="fieldActiveLocale = locale"
                >
                    {{ locale.toUpperCase() }}
                    <span
                        class="inline-block w-1.5 h-1.5 rounded-full"
                        :class="editingField.translations[locale]?.label?.trim() ? 'bg-emerald-400' : 'bg-muted/40'"
                    />
                </AppTab>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.fieldLabel") }}</label>
                <input
                    v-model="editingField.translations[fieldActiveLocale].label"
                    type="text"
                    class="w-full px-3 py-2 rounded-lg bg-surface-2 border text-sm text-primary focus:outline-none focus:ring-1 focus:ring-accent-500"
                    :class="localeFieldError(fieldErrors, fieldActiveLocale, 'label') ? 'border-rose-500' : 'border-line/60'"
                    :placeholder="t('backend.forms.fieldLabelPlaceholder')"
                >
                <p v-if="localeFieldError(fieldErrors, fieldActiveLocale, 'label')" class="text-xs text-rose-400">{{ localeFieldError(fieldErrors, fieldActiveLocale, 'label') }}</p>
            </div>

            <div v-if="!fieldHasOptions" class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.fieldPlaceholder") }}</label>
                <input
                    v-model="editingField.translations[fieldActiveLocale].placeholder"
                    type="text"
                    class="w-full px-3 py-2 rounded-lg bg-surface-2 border border-line/60 text-sm text-primary focus:outline-none focus:ring-1 focus:ring-accent-500"
                    :placeholder="t('backend.forms.fieldPlaceholderPlaceholder')"
                >
            </div>

            <div v-if="fieldHasOptions" class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.fieldOptions") }}</label>
                <textarea
                    v-model="fieldOptionsText[fieldActiveLocale]"
                    rows="4"
                    class="w-full px-3 py-2 rounded-lg bg-surface-2 border border-line/60 text-sm text-primary font-mono focus:outline-none focus:ring-1 focus:ring-accent-500 resize-none"
                    :placeholder="t('backend.forms.fieldOptionsPlaceholder')"
                />
                <p class="text-xs text-muted">{{ t("backend.forms.fieldOptionsHint") }}</p>
            </div>
        </div>

        <template #footer>
            <AppModalFooter bordered>
                <AppButton variant="ghost" size="md" v-on:click="showFieldModal = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="primary" size="md" :disabled="fieldSaving" v-on:click="submitField"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
            </AppModalFooter>
        </template>
    </AppModal>

    <AppModal
        :show="showDeleteConfirm"
        max-width="sm"
        :title="t('backend.forms.deleteConfirmTitle')"
        :icon="Trash2"
        :closeable="false"
        v-on:close="showDeleteConfirm = false"
    >
        <p class="text-sm text-secondary">{{ t("backend.forms.deleteConfirmBody", { title: formTitle(selectedForm) }) }}</p>
        <template #footer>
            <AppModalFooter bordered>
                <AppButton variant="ghost" size="md" v-on:click="showDeleteConfirm = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" :disabled="deleting" v-on:click="confirmDelete">
                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>

    <AppModal
        :show="!!viewingSubmission"
        max-width="md"
        :title="t('backend.forms.viewSubmission')"
        :closeable="false"
        v-on:close="viewingSubmission = null"
    >
        <div class="space-y-3">
            <div class="flex flex-col gap-1">
                <label class="text-xs text-secondary uppercase tracking-wide">{{ t("backend.forms.submittedAt") }}</label>
                <p class="text-sm text-muted">{{ viewingSubmission ? formatDateTime(viewingSubmission.submittedAt) : "" }}</p>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-secondary uppercase tracking-wide">{{ t("backend.forms.locale") }}</label>
                <p class="text-sm text-muted uppercase">{{ viewingSubmission?.locale }}</p>
            </div>
            <div v-for="field in submissionFields" :key="field.id" class="flex flex-col gap-1">
                <label class="text-xs text-secondary uppercase tracking-wide">{{ fieldLabel(field) }}</label>
                <p class="text-sm text-primary whitespace-pre-wrap bg-surface-2 rounded px-3 py-2">
                    {{ submissionValue(viewingSubmission, field) }}
                </p>
            </div>
        </div>
        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="viewingSubmission = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
            </AppModalFooter>
        </template>
    </AppModal>

    <FormPreviewModal
        :show="showPreview"
        :fields="editingForm.fields"
        :steps="editingForm.steps ?? []"
        :form-title="formTitle(editingForm)"
        :form-description="editingForm.translations?.[activeLocale]?.description ?? ''"
        :active-locale="activeLocale"
        :default-locale="defaultLocale()"
        v-on:close="showPreview = false"
    />

    <AppModal
        :show="!!pendingDeleteField"
        max-width="sm"
        :closeable="false"
        :title="t('shared.common.delete')"
        :icon="Trash2"
        v-on:close="pendingDeleteField = null"
    >
        <p class="text-sm text-primary">{{ t('backend.forms.deleteFieldConfirm', { label: pendingDeleteField ? fieldLabel(pendingDeleteField) : '' }) }}</p>
        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingDeleteField = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteFieldLoading" v-on:click="doDeleteField"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
