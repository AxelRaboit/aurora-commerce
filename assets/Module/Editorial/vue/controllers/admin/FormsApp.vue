<script setup>
import { HttpMethod } from "@/shared/utils/httpMethod.js";
import { ref, computed, onMounted } from "vue";
import { usePaginatedFetch } from "@/shared/composables/usePaginatedFetch.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { VueDraggable } from "vue-draggable-plus";
import {
    ClipboardList,
    Plus,
    Trash2,
    GripVertical,
    Pencil,
    Download,
    Settings,
    Layers,
    Inbox,
} from "lucide-vue-next";
import AppPagination from "@/shared/components/AppPagination.vue";
import AppButton from "@/shared/components/AppButton.vue";
import AppIconButton from "@/shared/components/AppIconButton.vue";
import AppNoData from "@/shared/components/AppNoData.vue";
import AppModal from "@/shared/components/AppModal.vue";
import AppModalFooter from "@/shared/components/AppModalFooter.vue";
import AppBadge from "@/shared/components/AppBadge.vue";
import { slugify } from "@/shared/utils/slugify.js";
import { useDateFormat } from "@/shared/composables/useDateFormat.js";

const { t } = useI18n();
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

// ── Forms list ───────────────────────────────────────────────────────────────
const { items: forms, loading, page, totalPages, total, load: fetchForms, goToPage, reset: resetForms } = usePaginatedFetch(
    () => props.listPath,
);

// ── Selected form ────────────────────────────────────────────────────────────
const selectedForm = ref(null);
const editingForm = ref(emptyForm());
const formErrors = ref({});
const saving = ref(false);
const activeTab = ref("settings");
const activeLocale = ref(props.locales[0] ?? "fr");
const showDeleteConfirm = ref(false);
const deleting = ref(false);

const isCreating = computed(() => null === selectedForm.value);
const slugLocked = ref(Object.fromEntries(props.locales.map((l) => [l, false])));
const sharedSlug = ref(false);

const tabs = computed(() => {
    if (isCreating.value) {
        return [{ key: "settings", label: t("admin.forms.tabs.settings"), icon: Settings }];
    }

    return [
        { key: "settings", label: t("admin.forms.tabs.settings"), icon: Settings },
        { key: "fields", label: t("admin.forms.tabs.fields"), icon: Layers },
        { key: "submissions", label: t("admin.forms.tabs.submissions"), icon: Inbox },
    ];
});

// ── Field modal ──────────────────────────────────────────────────────────────
const showFieldModal = ref(false);
const editingFieldId = ref(null);
const editingField = ref(emptyField());
const fieldOptionsText = ref({});
const fieldErrors = ref({});
const fieldSaving = ref(false);
const fieldActiveLocale = ref(props.locales[0] ?? "fr");

const FIELD_TYPES = computed(() => [
    { value: "text", label: t("admin.forms.fieldTypes.text") },
    { value: "email", label: t("admin.forms.fieldTypes.email") },
    { value: "textarea", label: t("admin.forms.fieldTypes.textarea") },
    { value: "number", label: t("admin.forms.fieldTypes.number") },
    { value: "tel", label: t("admin.forms.fieldTypes.tel") },
    { value: "date", label: t("admin.forms.fieldTypes.date") },
    { value: "select", label: t("admin.forms.fieldTypes.select") },
    { value: "radio", label: t("admin.forms.fieldTypes.radio") },
    { value: "checkbox", label: t("admin.forms.fieldTypes.checkbox") },
]);

const fieldHasOptions = computed(() => ["select", "radio", "checkbox"].includes(editingField.value.type));

// ── Submissions ──────────────────────────────────────────────────────────────
const submissionFields = ref([]);
const viewingSubmission = ref(null);

const {
    items: submissions,
    loading: submissionsLoading,
    page: submissionsPage,
    totalPages: submissionsTotalPages,
    total: submissionsTotal,
    load: fetchSubmissions,
    goToPage: goToSubmissionsPage,
    reset: resetSubmissions,
} = usePaginatedFetch(
    () => selectedForm.value ? props.submissionsPath.replace("__id__", selectedForm.value.id) : null,
    () => ({}),
    (data) => { submissionFields.value = data.fields ?? []; },
);

// ── Helpers ──────────────────────────────────────────────────────────────────
function emptyTranslations() {
    return Object.fromEntries(props.locales.map((l) => [l, { title: "", slug: "", description: "" }]));
}

function emptyForm() {
    return {
        notifyEmail: "",
        active: true,
        translations: emptyTranslations(),
        fields: [],
    };
}

function emptyFieldTranslations() {
    return Object.fromEntries(props.locales.map((l) => [l, { label: "", placeholder: "", options: [] }]));
}

function emptyField() {
    return { type: "text", required: false, translations: emptyFieldTranslations() };
}

function fieldTypeLabel(type) {
    return FIELD_TYPES.value.find((ft) => ft.value === type)?.label ?? type;
}

function defaultLocale() {
    return props.locales[0] ?? "fr";
}

function fieldLabel(field, locale = activeLocale.value) {
    return field?.translations?.[locale]?.label
        ?? field?.translations?.[defaultLocale()]?.label
        ?? "";
}

function formTitle(form, locale = activeLocale.value) {
    return form?.translations?.[locale]?.title
        ?? form?.translations?.[defaultLocale()]?.title
        ?? "";
}


async function jsonRequest(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: { "Content-Type": "application/json", ...(options.headers ?? {}) },
    });
    return response.json();
}

function localeFieldError(scope, locale, key) {
    return scope[`translations.${locale}.${key}`];
}

// ── Forms list operations ────────────────────────────────────────────────────
onMounted(fetchForms);

// ── Form selection ───────────────────────────────────────────────────────────
function startCreate() {
    selectedForm.value = null;
    activeTab.value = "settings";
    activeLocale.value = defaultLocale();
    editingForm.value = emptyForm();
    formErrors.value = {};
    slugLocked.value = Object.fromEntries(props.locales.map((l) => [l, false]));
    sharedSlug.value = false;
}

async function selectForm(form) {
    selectedForm.value = form;
    activeTab.value = "settings";
    activeLocale.value = defaultLocale();
    formErrors.value = {};
    try {
        const data = await jsonRequest(props.getPath.replace("__id__", form.id));
        if (data.ok) {
            applyFormResponse(data.form);
        }
    } catch {
        toast.error(t("shared.common.error"));
    }
}

function applyFormResponse(form) {
    selectedForm.value = form;
    const translations = emptyTranslations();
    for (const [locale, data] of Object.entries(form.translations ?? {})) {
        translations[locale] = {
            title: data.title ?? "",
            slug: data.slug ?? "",
            description: data.description ?? "",
        };
    }
    editingForm.value = {
        notifyEmail: form.notifyEmail ?? "",
        active: form.active,
        translations,
        fields: form.fields ?? [],
    };
    // Lock slug for locales that already have one
    slugLocked.value = Object.fromEntries(props.locales.map((l) => [l, !!translations[l]?.slug]));
    // Detect shared slug: only if 2+ locales filled and all slugs identical
    const slugs = props.locales.map((l) => translations[l]?.slug).filter((s) => s);
    sharedSlug.value = slugs.length >= 2 && slugs.every((s) => s === slugs[0]);
}

function onTitleInput() {
    const locale = activeLocale.value;
    const trans = editingForm.value.translations[locale];
    if (trans && !slugLocked.value[locale]) {
        trans.slug = slugify(trans.title);
        propagateSharedSlug();
    }
}

function onSlugInput() {
    slugLocked.value[activeLocale.value] = true;
    propagateSharedSlug();
}

function propagateSharedSlug() {
    if (!sharedSlug.value) return;
    const sourceSlug = editingForm.value.translations[activeLocale.value]?.slug ?? "";
    for (const locale of props.locales) {
        if (locale !== activeLocale.value && editingForm.value.translations[locale]) {
            editingForm.value.translations[locale].slug = sourceSlug;
            slugLocked.value[locale] = true;
        }
    }
}

function onSharedSlugToggle() {
    if (sharedSlug.value) propagateSharedSlug();
}

function isLocaleFilled(locale) {
    return !!editingForm.value.translations[locale]?.title?.trim();
}

// ── Form CRUD ────────────────────────────────────────────────────────────────
async function saveForm() {
    if (saving.value) return;
    saving.value = true;
    formErrors.value = {};

    const isNew = isCreating.value;
    const url = isNew ? props.createPath : props.updatePath.replace("__id__", selectedForm.value.id);
    const payload = {
        notifyEmail: editingForm.value.notifyEmail || null,
        active: editingForm.value.active,
        translations: editingForm.value.translations,
    };

    try {
        const data = await jsonRequest(url, { method: HttpMethod.Post, body: JSON.stringify(payload) });
        if (data.ok) {
            toast.success(t("shared.common.saved"));
            applyFormResponse(data.form);
            await fetchForms();
        } else if (data.errors) {
            formErrors.value = data.errors;
            const firstErrorLocale = Object.keys(data.errors).find((k) => k.startsWith("translations."))?.split(".")[1];
            if (firstErrorLocale) activeLocale.value = firstErrorLocale;
        } else {
            toast.error(t("shared.common.error"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        saving.value = false;
    }
}

async function confirmDelete() {
    deleting.value = true;
    try {
        const data = await jsonRequest(props.deletePath.replace("__id__", selectedForm.value.id), { method: HttpMethod.Post });
        if (data.ok) {
            toast.success(t("shared.common.deleted"));
            selectedForm.value = null;
            await fetchForms();
        } else {
            toast.error(t("shared.common.error"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        deleting.value = false;
        showDeleteConfirm.value = false;
    }
}

// ── Field CRUD ───────────────────────────────────────────────────────────────
function openAddField() {
    editingFieldId.value = null;
    editingField.value = emptyField();
    fieldOptionsText.value = Object.fromEntries(props.locales.map((l) => [l, ""]));
    fieldErrors.value = {};
    fieldActiveLocale.value = defaultLocale();
    showFieldModal.value = true;
}

function openEditField(field) {
    editingFieldId.value = field.id;
    const translations = emptyFieldTranslations();
    for (const [locale, data] of Object.entries(field.translations ?? {})) {
        translations[locale] = {
            label: data.label ?? "",
            placeholder: data.placeholder ?? "",
            options: [...(data.options ?? [])],
        };
    }
    editingField.value = {
        type: field.type,
        required: field.required,
        translations,
    };
    fieldOptionsText.value = Object.fromEntries(
        props.locales.map((l) => [l, (translations[l]?.options ?? []).join("\n")]),
    );
    fieldErrors.value = {};
    fieldActiveLocale.value = defaultLocale();
    showFieldModal.value = true;
}

async function submitField() {
    if (!selectedForm.value || fieldSaving.value) return;

    fieldErrors.value = {};
    fieldSaving.value = true;

    // Build translations payload, including options from textarea
    const translations = {};
    for (const locale of props.locales) {
        const trans = editingField.value.translations[locale] ?? { label: "", placeholder: "", options: [] };
        const options = fieldHasOptions.value
            ? (fieldOptionsText.value[locale] ?? "").split("\n").map((s) => s.trim()).filter(Boolean)
            : [];
        translations[locale] = {
            label: trans.label,
            placeholder: trans.placeholder || null,
            options,
        };
    }

    const payload = {
        type: editingField.value.type,
        required: editingField.value.required,
        translations,
    };

    const isUpdate = editingFieldId.value !== null;
    const url = isUpdate
        ? props.fieldUpdatePath
            .replace("__id__", selectedForm.value.id)
            .replace("__fieldId__", editingFieldId.value)
        : props.fieldCreatePath.replace("__id__", selectedForm.value.id);

    try {
        const data = await jsonRequest(url, { method: HttpMethod.Post, body: JSON.stringify(payload) });
        if (data.ok) {
            toast.success(t("shared.common.saved"));
            if (isUpdate) {
                const index = editingForm.value.fields.findIndex((f) => f.id === editingFieldId.value);
                if (index !== -1) editingForm.value.fields[index] = data.field;
            } else {
                editingForm.value.fields.push(data.field);
            }
            showFieldModal.value = false;
        } else if (data.errors) {
            fieldErrors.value = data.errors;
            const firstErrorLocale = Object.keys(data.errors).find((k) => k.startsWith("translations."))?.split(".")[1];
            if (firstErrorLocale) fieldActiveLocale.value = firstErrorLocale;
        } else {
            toast.error(t("shared.common.error"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        fieldSaving.value = false;
    }
}

const pendingDeleteField = ref(null);
const deleteFieldLoading = ref(false);

function confirmDeleteField(field) {
    pendingDeleteField.value = field;
}

async function doDeleteField() {
    if (!selectedForm.value || !pendingDeleteField.value || deleteFieldLoading.value) return;
    deleteFieldLoading.value = true;
    const field = pendingDeleteField.value;
    const url = props.fieldDeletePath
        .replace("__id__", selectedForm.value.id)
        .replace("__fieldId__", field.id);

    try {
        const data = await jsonRequest(url, { method: HttpMethod.Post });
        if (data.ok) {
            toast.success(t("shared.common.deleted"));
            editingForm.value.fields = editingForm.value.fields.filter((f) => f.id !== field.id);
            pendingDeleteField.value = null;
        } else {
            toast.error(t("shared.common.error"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        deleteFieldLoading.value = false;
    }
}

async function onFieldsReordered() {
    if (!selectedForm.value) return;
    const orderedIds = editingForm.value.fields.map((f) => f.id);
    const url = props.fieldReorderPath.replace("__id__", selectedForm.value.id);
    try {
        const data = await jsonRequest(url, { method: HttpMethod.Post, body: JSON.stringify({ orderedIds }) });
        if (!data.ok) toast.error(t("shared.common.error"));
    } catch {
        toast.error(t("shared.common.error"));
    }
}

// ── Submissions ──────────────────────────────────────────────────────────────
function onTabChange(tab) {
    activeTab.value = tab;
    if (tab === "submissions") {
        resetSubmissions();
    }
}

function exportCsv() {
    const url = `${props.exportPath.replace("__id__", selectedForm.value.id)}?locale=${activeLocale.value}`;
    window.location.href = url;
}

function submissionValue(submission, field) {
    const value = submission?.data?.[field.id];
    if (Array.isArray(value)) return value.join(", ");
    return value ?? "—";
}
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]">
        <!-- Left panel: forms list -->
        <div class="lg:w-72 shrink-0 flex flex-col gap-3">
            <AppButton variant="primary" size="md" class="w-full justify-center" v-on:click="startCreate">
                <Plus class="w-4 h-4 shrink-0" :stroke-width="2" />
                {{ t("admin.forms.create") }}
            </AppButton>

            <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
                <AppNoData v-if="!loading && !forms.length" :message="t('admin.forms.empty')" />
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
                            <p class="text-xs text-muted">{{ form.submissionCount }} {{ t("admin.forms.submissions_count") }}</p>
                        </div>
                        <AppBadge v-if="!form.active" color="gray" class="shrink-0">{{ t("admin.forms.inactive") }}</AppBadge>
                    </li>
                </ul>
            </div>

            <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />
        </div>

        <!-- Right panel: editor -->
        <div v-if="selectedForm || isCreating" class="flex-1 min-w-0 min-h-0 bg-surface border border-line/60 rounded-xl overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-line/60">
                <div class="flex items-center gap-2 min-w-0">
                    <ClipboardList class="w-5 h-5 shrink-0 text-accent-400" :stroke-width="2" />
                    <h2 class="text-base font-semibold text-primary truncate">
                        {{ isCreating ? t("admin.forms.newForm") : (formTitle(selectedForm) || "—") }}
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <AppButton
                        v-if="!isCreating"
                        variant="danger"
                        size="md"
                        v-on:click="showDeleteConfirm = true"
                    >
                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                    <AppButton
                        v-if="activeTab === 'settings'"
                        variant="primary"
                        size="md"
                        :disabled="saving"
                        v-on:click="saveForm"
                    >
                        {{ saving ? t("shared.common.loading") : t("shared.common.save") }}
                    </AppButton>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 px-5 pt-3 border-b border-line/60">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-t-md transition-colors -mb-px border-b-2"
                    :class="activeTab === tab.key ? 'text-accent-400 border-accent-400' : 'text-secondary hover:text-primary border-transparent'"
                    v-on:click="onTabChange(tab.key)"
                >
                    <component :is="tab.icon" class="w-4 h-4" :stroke-width="2" />
                    {{ tab.label }}
                </button>
            </div>

            <!-- Settings tab -->
            <div v-if="activeTab === 'settings'" class="p-5 space-y-4 overflow-y-auto flex-1">
                <!-- Locale switcher -->
                <div v-if="locales.length > 1" class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                    <div class="flex gap-1">
                        <button
                            v-for="locale in locales"
                            :key="locale"
                            type="button"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded transition-colors"
                            :class="activeLocale === locale ? 'bg-accent-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                            v-on:click="activeLocale = locale"
                        >
                            {{ locale.toUpperCase() }}
                            <span
                                class="inline-block w-1.5 h-1.5 rounded-full"
                                :class="isLocaleFilled(locale) ? 'bg-emerald-400' : 'bg-muted/40'"
                                :title="isLocaleFilled(locale) ? t('admin.forms.localeFilled') : t('admin.forms.localeEmpty')"
                            />
                        </button>
                    </div>
                    <p class="text-xs text-muted">{{ t("admin.forms.localesOptional") }}</p>
                </div>

                <!-- Per-locale fields -->
                <div :class="sharedSlug ? '' : 'grid grid-cols-1 sm:grid-cols-2 gap-4'">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.forms.title") }}</label>
                        <input
                            v-model="editingForm.translations[activeLocale].title"
                            type="text"
                            class="w-full px-3 py-2 rounded-lg bg-surface-2 border text-sm text-primary placeholder-muted focus:outline-none focus:ring-1 focus:ring-accent-500"
                            :class="localeFieldError(formErrors, activeLocale, 'title') ? 'border-rose-500' : 'border-line/60'"
                            :placeholder="t('admin.forms.titlePlaceholder')"
                            v-on:input="onTitleInput"
                        >
                        <p v-if="localeFieldError(formErrors, activeLocale, 'title')" class="text-xs text-rose-400">{{ localeFieldError(formErrors, activeLocale, 'title') }}</p>
                    </div>
                    <div v-if="!sharedSlug" class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.forms.slug") }}</label>
                        <input
                            v-model="editingForm.translations[activeLocale].slug"
                            type="text"
                            class="w-full px-3 py-2 rounded-lg bg-surface-2 border text-sm text-primary placeholder-muted focus:outline-none focus:ring-1 focus:ring-accent-500 font-mono"
                            :class="localeFieldError(formErrors, activeLocale, 'slug') ? 'border-rose-500' : 'border-line/60'"
                            :placeholder="t('admin.forms.slugPlaceholder')"
                            v-on:input="onSlugInput"
                        >
                        <p v-if="localeFieldError(formErrors, activeLocale, 'slug')" class="text-xs text-rose-400">{{ localeFieldError(formErrors, activeLocale, 'slug') }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.forms.description") }}</label>
                    <textarea
                        v-model="editingForm.translations[activeLocale].description"
                        rows="3"
                        class="w-full px-3 py-2 rounded-lg bg-surface-2 border border-line/60 text-sm text-primary placeholder-muted focus:outline-none focus:ring-1 focus:ring-accent-500 resize-none"
                        :placeholder="t('admin.forms.descriptionPlaceholder')"
                    />
                </div>

                <hr class="border-line/40">

                <!-- Slug + shared toggle -->
                <div class="space-y-2">
                    <div v-if="locales.length > 1" class="flex items-center gap-2">
                        <input
                            id="shared-slug"
                            v-model="sharedSlug"
                            type="checkbox"
                            class="rounded border-line text-accent-600 focus:ring-accent-500"
                            v-on:change="onSharedSlugToggle"
                        >
                        <label for="shared-slug" class="text-sm text-primary cursor-pointer">{{ t("admin.forms.sharedSlug") }}</label>
                    </div>

                    <div v-if="sharedSlug" class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.forms.slug") }}</label>
                        <input
                            v-model="editingForm.translations[activeLocale].slug"
                            type="text"
                            class="w-full px-3 py-2 rounded-lg bg-surface-2 border text-sm text-primary placeholder-muted focus:outline-none focus:ring-1 focus:ring-accent-500 font-mono"
                            :class="localeFieldError(formErrors, activeLocale, 'slug') ? 'border-rose-500' : 'border-line/60'"
                            :placeholder="t('admin.forms.slugPlaceholder')"
                            v-on:input="onSlugInput"
                        >
                        <p v-if="localeFieldError(formErrors, activeLocale, 'slug')" class="text-xs text-rose-400">{{ localeFieldError(formErrors, activeLocale, 'slug') }}</p>
                    </div>
                </div>

                <hr class="border-line/40">

                <!-- Global settings -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.forms.notifyEmail") }}</label>
                    <input
                        v-model="editingForm.notifyEmail"
                        type="email"
                        class="w-full px-3 py-2 rounded-lg bg-surface-2 border border-line/60 text-sm text-primary placeholder-muted focus:outline-none focus:ring-1 focus:ring-accent-500"
                        :placeholder="t('admin.forms.notifyEmailPlaceholder')"
                    >
                    <p class="text-xs text-muted">{{ t("admin.forms.notifyEmailHint") }}</p>
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
                    <label class="text-sm text-primary cursor-pointer" v-on:click="editingForm.active = !editingForm.active">{{ t("admin.forms.active") }}</label>
                </div>
            </div>

            <!-- Fields tab -->
            <div v-if="activeTab === 'fields'" class="p-5 flex flex-col gap-4 overflow-y-auto flex-1">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-secondary">{{ t("admin.forms.fieldsHint") }}</p>
                    <AppButton variant="secondary" size="md" v-on:click="openAddField">
                        <Plus class="w-4 h-4" :stroke-width="2" />
                        {{ t("admin.forms.addField") }}
                    </AppButton>
                </div>

                <div v-if="editingForm.fields.length === 0" class="py-10 text-center text-sm text-muted">
                    {{ t("admin.forms.fieldsEmpty") }}
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
                                <AppBadge v-if="field.required" color="amber">{{ t("admin.forms.required") }}</AppBadge>
                                <AppBadge color="accent">{{ fieldTypeLabel(field.type) }}</AppBadge>
                            </div>
                        </div>
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEditField(field)">
                            <Pencil class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDeleteField(field)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </VueDraggable>
            </div>

            <!-- Submissions tab -->
            <div v-if="activeTab === 'submissions'" class="flex flex-col gap-4 overflow-y-auto flex-1">
                <div class="flex items-center justify-between px-5 pt-5">
                    <p class="text-sm text-secondary">{{ submissionsTotal }} {{ t("admin.forms.submissionsCount") }}</p>
                    <AppButton variant="secondary" size="md" :disabled="!submissions.length" v-on:click="exportCsv">
                        <Download class="w-4 h-4" :stroke-width="2" />
                        {{ t("admin.forms.exportCsv") }}
                    </AppButton>
                </div>

                <div class="bg-surface border-t border-line/60 overflow-hidden">
                    <AppNoData v-if="!submissionsLoading && !submissions.length" :message="t('admin.forms.submissionsEmpty')" />
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-surface-2 text-xs text-secondary uppercase tracking-wide">
                                <tr>
                                    <th class="text-left px-4 py-3 font-semibold whitespace-nowrap">{{ t("admin.forms.submittedAt") }}</th>
                                    <th class="text-left px-4 py-3 font-semibold whitespace-nowrap">{{ t("admin.forms.locale") }}</th>
                                    <th v-for="field in submissionFields" :key="field.id" class="text-left px-4 py-3 font-semibold whitespace-nowrap max-w-xs">{{ fieldLabel(field) }}</th>
                                    <th class="text-right px-4 py-3 font-semibold">{{ t("shared.common.edit") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="submission in submissions" :key="submission.id" class="border-t border-line/60 hover:bg-surface-2/50">
                                    <td class="px-4 py-3 text-xs text-muted whitespace-nowrap">{{ formatDateTime(submission.submittedAt) }}</td>
                                    <td class="px-4 py-3 text-xs text-muted whitespace-nowrap uppercase">{{ submission.locale }}</td>
                                    <td v-for="field in submissionFields" :key="field.id" class="px-4 py-3 text-sm text-secondary max-w-xs truncate">
                                        {{ submissionValue(submission, field) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <AppIconButton color="accent" :title="t('admin.forms.viewSubmission')" v-on:click="viewingSubmission = submission">
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

        <!-- Empty state -->
        <div v-else class="flex-1 flex items-center justify-center text-sm text-muted bg-surface border border-line/60 rounded-xl">
            {{ t("admin.forms.selectOrCreate") }}
        </div>
    </div>

    <!-- Field modal -->
    <AppModal :show="showFieldModal" max-width="sm" v-on:close="showFieldModal = false">
        <h3 class="text-base font-semibold text-primary">
            {{ editingFieldId !== null ? t("admin.forms.editField") : t("admin.forms.addFieldTitle") }}
        </h3>
        <div class="space-y-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.forms.fieldType") }}</label>
                <select
                    v-model="editingField.type"
                    class="w-full px-3 py-2 rounded-lg bg-surface-2 border border-line/60 text-sm text-primary focus:outline-none focus:ring-1 focus:ring-accent-500"
                >
                    <option v-for="ft in FIELD_TYPES" :key="ft.value" :value="ft.value">{{ ft.label }}</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input id="field-required" v-model="editingField.required" type="checkbox" class="rounded border-line text-accent-600 focus:ring-accent-500">
                <label for="field-required" class="text-sm text-primary">{{ t("admin.forms.fieldRequired") }}</label>
            </div>

            <hr class="border-line/40">

            <!-- Locale switcher for field translations -->
            <div v-if="locales.length > 1" class="flex gap-1">
                <button
                    v-for="locale in locales"
                    :key="locale"
                    type="button"
                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded transition-colors"
                    :class="fieldActiveLocale === locale ? 'bg-accent-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                    v-on:click="fieldActiveLocale = locale"
                >
                    {{ locale.toUpperCase() }}
                    <span
                        class="inline-block w-1.5 h-1.5 rounded-full"
                        :class="editingField.translations[locale]?.label?.trim() ? 'bg-emerald-400' : 'bg-muted/40'"
                    />
                </button>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.forms.fieldLabel") }}</label>
                <input
                    v-model="editingField.translations[fieldActiveLocale].label"
                    type="text"
                    class="w-full px-3 py-2 rounded-lg bg-surface-2 border text-sm text-primary focus:outline-none focus:ring-1 focus:ring-accent-500"
                    :class="localeFieldError(fieldErrors, fieldActiveLocale, 'label') ? 'border-rose-500' : 'border-line/60'"
                    :placeholder="t('admin.forms.fieldLabelPlaceholder')"
                >
                <p v-if="localeFieldError(fieldErrors, fieldActiveLocale, 'label')" class="text-xs text-rose-400">{{ localeFieldError(fieldErrors, fieldActiveLocale, 'label') }}</p>
            </div>

            <div v-if="!fieldHasOptions" class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.forms.fieldPlaceholder") }}</label>
                <input
                    v-model="editingField.translations[fieldActiveLocale].placeholder"
                    type="text"
                    class="w-full px-3 py-2 rounded-lg bg-surface-2 border border-line/60 text-sm text-primary focus:outline-none focus:ring-1 focus:ring-accent-500"
                    :placeholder="t('admin.forms.fieldPlaceholderPlaceholder')"
                >
            </div>

            <div v-if="fieldHasOptions" class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("admin.forms.fieldOptions") }}</label>
                <textarea
                    v-model="fieldOptionsText[fieldActiveLocale]"
                    rows="4"
                    class="w-full px-3 py-2 rounded-lg bg-surface-2 border border-line/60 text-sm text-primary font-mono focus:outline-none focus:ring-1 focus:ring-accent-500 resize-none"
                    :placeholder="t('admin.forms.fieldOptionsPlaceholder')"
                />
                <p class="text-xs text-muted">{{ t("admin.forms.fieldOptionsHint") }}</p>
            </div>
        </div>

        <AppModalFooter bordered>
            <AppButton variant="ghost" size="md" v-on:click="showFieldModal = false">{{ t("shared.common.cancel") }}</AppButton>
            <AppButton variant="primary" size="md" :disabled="fieldSaving" v-on:click="submitField">{{ t("shared.common.save") }}</AppButton>
        </AppModalFooter>
    </AppModal>

    <!-- Delete confirm modal -->
    <AppModal :show="showDeleteConfirm" max-width="sm" v-on:close="showDeleteConfirm = false">
        <h3 class="text-base font-semibold text-primary">{{ t("admin.forms.deleteConfirmTitle") }}</h3>
        <p class="text-sm text-secondary">{{ t("admin.forms.deleteConfirmBody", { title: formTitle(selectedForm) }) }}</p>
        <AppModalFooter bordered>
            <AppButton variant="ghost" size="md" v-on:click="showDeleteConfirm = false">{{ t("shared.common.cancel") }}</AppButton>
            <AppButton variant="danger" size="md" :disabled="deleting" v-on:click="confirmDelete">
                {{ t("shared.common.delete") }}
            </AppButton>
        </AppModalFooter>
    </AppModal>

    <!-- Submission detail modal -->
    <AppModal :show="!!viewingSubmission" max-width="md" v-on:close="viewingSubmission = null">
        <h3 class="text-base font-semibold text-primary">{{ t("admin.forms.viewSubmission") }}</h3>
        <div class="space-y-3">
            <div class="flex flex-col gap-1">
                <label class="text-xs text-secondary uppercase tracking-wide">{{ t("admin.forms.submittedAt") }}</label>
                <p class="text-sm text-muted">{{ viewingSubmission ? formatDateTime(viewingSubmission.submittedAt) : "" }}</p>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-secondary uppercase tracking-wide">{{ t("admin.forms.locale") }}</label>
                <p class="text-sm text-muted uppercase">{{ viewingSubmission?.locale }}</p>
            </div>
            <div v-for="field in submissionFields" :key="field.id" class="flex flex-col gap-1">
                <label class="text-xs text-secondary uppercase tracking-wide">{{ fieldLabel(field) }}</label>
                <p class="text-sm text-primary whitespace-pre-wrap bg-surface-2 rounded px-3 py-2">
                    {{ submissionValue(viewingSubmission, field) }}
                </p>
            </div>
        </div>
        <div class="flex justify-end pt-2 border-t border-line">
            <AppButton variant="ghost" size="md" v-on:click="viewingSubmission = null">{{ t("shared.common.cancel") }}</AppButton>
        </div>
    </AppModal>

    <!-- Delete field confirm -->
    <AppModal :show="!!pendingDeleteField" max-width="sm" v-on:close="pendingDeleteField = null">
        <p class="text-sm text-primary">{{ t('admin.forms.deleteFieldConfirm', { label: pendingDeleteField ? fieldLabel(pendingDeleteField) : '' }) }}</p>
        <div class="flex justify-end gap-2 pt-3 border-t border-line">
            <AppButton variant="ghost" size="md" v-on:click="pendingDeleteField = null">{{ t('shared.common.cancel') }}</AppButton>
            <AppButton variant="danger" size="md" :loading="deleteFieldLoading" v-on:click="doDeleteField">{{ t('shared.common.delete') }}</AppButton>
        </div>
    </AppModal>
</template>
