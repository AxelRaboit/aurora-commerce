<script setup>
import { HttpMethod } from "@/shared/utils/httpMethod.js";
import { ref, reactive, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormModal } from "@/shared/composables/useFormModal.js";
import { VueDraggable } from "vue-draggable-plus";
import { Plus, Pencil, Trash2, Layers, Lock, GripVertical, Save, } from "lucide-vue-next";
import AppButton from "@/shared/components/AppButton.vue";
import AppIconButton from "@/shared/components/AppIconButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AppSelect from "@/shared/components/AppSelect.vue";
import AppMultiselect from "@/shared/components/AppMultiselect.vue";
import AppCheckbox from "@/shared/components/AppCheckbox.vue";
import AppModal from "@/shared/components/AppModal.vue";
import AppMessage from "@/shared/components/AppMessage.vue";
import AppNoData from "@/shared/components/AppNoData.vue";
import AppBadge from "@/shared/components/AppBadge.vue";

const { t } = useI18n();

const props = defineProps({
    postTypes: { type: Array, default: () => [] },
    taxonomies: { type: Array, default: () => [] },
    createPath: { type: String, required: true },
    editPath: { type: String, required: true },
    deletePath: { type: String, required: true },
    fieldCreatePath: { type: String, required: true },
    fieldEditPath: { type: String, required: true },
    fieldDeletePath: { type: String, required: true },
    fieldReorderPath: { type: String, required: true },
});

const FIELD_TYPES = ["text", "textarea", "number", "date", "select", "checkbox", "media", "url", "email", "reference"];
const SUPPORTS = ["blocks", "thumbnail", "excerpt"];

const postTypes = ref([...props.postTypes]);
const selectedId = ref(postTypes.value[0]?.id ?? null);
const selected = computed(() => postTypes.value.find((pt) => pt.id === selectedId.value) ?? null);

function replacePostType(fresh) {
    const idx = postTypes.value.findIndex((pt) => pt.id === fresh.id);
    if (idx === -1) postTypes.value.push(fresh);
    else postTypes.value[idx] = fresh;
}

// ── Post type modal ──────────────────────────────────────────────────────────
const { modal: postTypeModal, openCreate: postTypeModalCreate, openEdit: postTypeModalEdit, submit: postTypeModalSubmit } = useFormModal();
const postTypeForm = reactive({ slug: "", label: "", icon: "", hasArchive: false, supports: [], taxonomyIds: [] });

function openCreatePostType() {
    postTypeModalCreate(() => Object.assign(postTypeForm, {
        slug: "", label: "", icon: "", hasArchive: false, supports: [...SUPPORTS], taxonomyIds: [],
    }));
}

function openEditPostType(postType) {
    postTypeModalEdit(postType, (pt) => Object.assign(postTypeForm, {
        slug: pt.slug, label: pt.label, icon: pt.icon ?? "",
        hasArchive: pt.hasArchive, supports: [...(pt.supports ?? [])], taxonomyIds: [...(pt.taxonomyIds ?? [])],
    }));
}

async function submitPostType() {
    const url = postTypeModal.editing
        ? props.editPath.replace("__id__", postTypeModal.editing.id)
        : props.createPath;
    await postTypeModalSubmit(url, postTypeForm, (data) => {
        replacePostType(data.postType);
        selectedId.value = data.postType.id;
    });
}

const deletingPostType = ref(null);
async function confirmDeletePostType() {
    const pt = deletingPostType.value;
    if (!pt) return;
    try {
        const response = await fetch(props.deletePath.replace("__id__", pt.id), { method: HttpMethod.Post });
        const data = await response.json();
        if (!data.success) {
            toast.error(data.error ? t(data.error) : t("shared.common.error"));
            return;
        }
        postTypes.value = postTypes.value.filter((p) => p.id !== pt.id);
        if (selectedId.value === pt.id) selectedId.value = postTypes.value[0]?.id ?? null;
        toast.success(t("shared.common.deleted"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        deletingPostType.value = null;
    }
}

// ── Field modal ──────────────────────────────────────────────────────────────
const { modal: fieldModal, openCreate: fieldModalCreate, openEdit: fieldModalEdit, submit: fieldModalSubmit } = useFormModal();
const fieldForm = reactive({
    name: "", label: "", type: "text", required: false, translatable: false,
    choicesText: "", referencePostTypeId: null, referenceMultiple: false,
});

function openCreateField() {
    if (!selected.value) return;
    fieldModalCreate(() => Object.assign(fieldForm, {
        name: "", label: "", type: "text", required: false, translatable: false,
        choicesText: "", referencePostTypeId: null, referenceMultiple: false,
    }));
}

function openEditField(field) {
    fieldModalEdit(field, (f) => {
        const options = f.options ?? {};
        Object.assign(fieldForm, {
            name: f.name, label: f.label, type: f.type, required: f.required, translatable: f.translatable,
            choicesText: (options.choices ?? []).map((c) => `${c.value}|${c.label}`).join("\n"),
            referencePostTypeId: options.postTypeId ?? null,
            referenceMultiple: options.multiple ?? false,
        });
    });
}

function buildFieldOptions() {
    if (fieldForm.type === "select") {
        return { choices: fieldForm.choicesText.split("\n").map((l) => l.trim()).filter(Boolean).map((l) => {
            const [value, ...rest] = l.split("|");
            return { value: value.trim(), label: rest.join("|").trim() || value.trim() };
        }) };
    }
    if (fieldForm.type === "reference") {
        const options = { multiple: fieldForm.referenceMultiple };
        if (fieldForm.referencePostTypeId) options.postTypeId = Number(fieldForm.referencePostTypeId);
        return options;
    }
    return {};
}

async function submitField() {
    if (!selected.value) return;
    const url = fieldModal.editing
        ? props.fieldEditPath.replace("__id__", selected.value.id).replace("__fieldId__", fieldModal.editing.id)
        : props.fieldCreatePath.replace("__id__", selected.value.id);
    const payload = {
        name: fieldForm.name, label: fieldForm.label, type: fieldForm.type,
        required: fieldForm.required, translatable: fieldForm.translatable, options: buildFieldOptions(),
    };
    await fieldModalSubmit(url, payload, (data) => replacePostType(data.postType));
}

const deletingField = ref(null);
async function confirmDeleteField() {
    const field = deletingField.value;
    if (!field || !selected.value) return;
    try {
        const url = props.fieldDeletePath.replace("__id__", selected.value.id).replace("__fieldId__", field.id);
        const response = await fetch(url, { method: HttpMethod.Post });
        const data = await response.json();
        if (!data.success) {
            toast.error(t("shared.common.error"));
            return;
        }
        replacePostType(data.postType);
        toast.success(t("shared.common.deleted"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        deletingField.value = null;
    }
}

// ── Drag & drop reorder for fields ───────────────────────────────────────────
const orderedFields = ref([]);

watch(
    () => selected.value?.fields,
    (fields) => {
        orderedFields.value = [...(fields ?? [])].sort((a, b) => a.position - b.position);
    },
    { immediate: true, deep: true },
);

async function persistFieldOrder() {
    if (!selected.value) return;
    try {
        const response = await fetch(props.fieldReorderPath.replace("__id__", selected.value.id), {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ orderedIds: orderedFields.value.map((f) => f.id) }),
        });
        const data = await response.json();
        if (!data.success) {
            toast.error(t("shared.common.error"));
            return;
        }
        replacePostType(data.postType);
    } catch {
        toast.error(t("shared.common.error"));
    }
}

function toggleIn(list, value) {
    return list.includes(value) ? list.filter((item) => item !== value) : [...list, value];
}
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]">
        <aside class="lg:w-72 shrink-0 space-y-2">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("admin.postTypes.title") }}</h2>
                <AppButton variant="primary" size="md" v-on:click="openCreatePostType">
                    <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("admin.postTypes.add") }}
                </AppButton>
            </div>
            <div class="space-y-1">
                <button
                    v-for="postType in postTypes"
                    :key="postType.id"
                    type="button"
                    class="w-full text-left px-3 py-2 rounded-lg transition-colors flex items-center gap-2"
                    :class="selectedId === postType.id
                        ? 'bg-accent-600/15 text-accent-400 border border-accent-600/30'
                        : 'bg-surface hover:bg-surface-2 text-primary border border-line/60'"
                    v-on:click="selectedId = postType.id"
                >
                    <Layers class="w-4 h-4 shrink-0" :stroke-width="2" />
                    <span class="flex-1 text-sm font-medium truncate">{{ postType.label }}</span>
                    <Lock v-if="postType.isBuiltIn" class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" :title="t('admin.postTypes.builtIn')" />
                </button>
            </div>
        </aside>

        <main class="flex-1 min-w-0 space-y-4">
            <AppNoData v-if="!selected" :message="t('admin.postTypes.empty')" />
            <div v-else class="space-y-4">
                <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-primary">{{ selected.label }}</h3>
                            <p class="text-xs text-muted font-mono mt-0.5">{{ selected.slug }}</p>
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <AppBadge v-if="selected.isBuiltIn" color="amber">
                                    <Lock class="w-3 h-3" :stroke-width="2" />
                                    {{ t("admin.postTypes.builtIn") }}
                                </AppBadge>
                                <AppBadge v-if="selected.hasArchive" color="sky">{{ t("admin.postTypes.hasArchive") }}</AppBadge>
                                <AppBadge v-for="support in selected.supports" :key="support" color="gray">{{ support }}</AppBadge>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <AppButton variant="ghost" size="md" v-on:click="openEditPostType(selected)">
                                <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("shared.common.edit") }}
                            </AppButton>
                            <AppButton
                                v-if="!selected.isBuiltIn"
                                variant="danger"
                                size="md"
                                v-on:click="deletingPostType = selected"
                            >
                                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("shared.common.delete") }}
                            </AppButton>
                        </div>
                    </div>
                </div>

                <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <h4 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("admin.postTypes.fields.title") }}</h4>
                        <AppButton variant="primary" size="md" v-on:click="openCreateField">
                            <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("admin.postTypes.fields.add") }}
                        </AppButton>
                    </div>

                    <AppNoData v-if="!orderedFields.length" :message="t('admin.postTypes.fields.empty')" />
                    <AppMessage v-else variant="info">
                        {{ t("admin.postTypes.fields.dndHint") }}
                    </AppMessage>

                    <VueDraggable
                        v-if="orderedFields.length"
                        v-model="orderedFields"
                        handle=".drag-handle"
                        :animation="150"
                        ghost-class="opacity-50"
                        class="space-y-1"
                        v-on:end="persistFieldOrder"
                    >
                        <div
                            v-for="field in orderedFields"
                            :key="field.id"
                            class="flex items-center gap-2 px-3 py-2 rounded-md border border-line bg-surface-2"
                        >
                            <button type="button" class="drag-handle cursor-grab active:cursor-grabbing text-muted hover:text-primary p-1">
                                <GripVertical class="w-4 h-4" :stroke-width="2" />
                            </button>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-primary truncate">{{ field.label }}</div>
                                <div class="text-xs text-muted font-mono truncate">{{ field.name }}</div>
                            </div>
                            <AppBadge color="gray">{{ field.type }}</AppBadge>
                            <AppBadge v-if="field.required" color="rose">{{ t("admin.postTypes.fields.required") }}</AppBadge>
                            <AppBadge v-if="field.translatable" color="sky">{{ t("admin.postTypes.fields.translatable") }}</AppBadge>
                            <div class="flex items-center gap-0.5">
                                <AppIconButton color="accent" v-on:click="openEditField(field)">
                                    <Pencil class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton color="rose" v-on:click="deletingField = field">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </div>
                    </VueDraggable>
                </div>
            </div>
        </main>

        <AppModal :show="postTypeModal.open" max-width="lg" v-on:close="postTypeModal.open = false">
            <h3 class="text-lg font-semibold text-primary">
                {{ postTypeModal.editing ? t("admin.postTypes.editPostType") : t("admin.postTypes.add") }}
            </h3>
            <form class="space-y-4" v-on:submit.prevent="submitPostType">
                <AppInput
                    v-model="postTypeForm.slug"
                    :label="t('admin.postTypes.slug')"
                    :error="postTypeModal.errors.slug ?? ''"
                    :disabled="postTypeModal.editing?.isBuiltIn ?? false"
                    :placeholder="t('admin.postTypes.slugPlaceholder')"
                />
                <AppInput
                    v-model="postTypeForm.label"
                    :label="t('admin.postTypes.label')"
                    :error="postTypeModal.errors.label ?? ''"
                    :placeholder="t('admin.postTypes.labelPlaceholder')"
                />
                <AppInput
                    v-model="postTypeForm.icon"
                    :label="t('admin.postTypes.icon')"
                    :placeholder="t('admin.postTypes.iconPlaceholder')"
                />
                <AppCheckbox v-model="postTypeForm.hasArchive" :label="t('admin.postTypes.hasArchive')" />

                <div class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t("admin.postTypes.supports") }}</label>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="support in SUPPORTS"
                            :key="support"
                            class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium cursor-pointer border transition-colors"
                            :class="postTypeForm.supports.includes(support)
                                ? 'bg-accent-600 border-accent-600 text-white'
                                : 'bg-surface-2 border-line text-secondary hover:border-accent-400'"
                        >
                            <input
                                type="checkbox"
                                class="sr-only"
                                :checked="postTypeForm.supports.includes(support)"
                                v-on:change="postTypeForm.supports = toggleIn(postTypeForm.supports, support)"
                            >
                            {{ support }}
                        </label>
                    </div>
                </div>

                <div v-if="taxonomies.length" class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t("admin.taxonomies.title") }}</label>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="taxonomy in taxonomies"
                            :key="taxonomy.id"
                            class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium cursor-pointer border transition-colors"
                            :class="postTypeForm.taxonomyIds.includes(taxonomy.id)
                                ? 'bg-accent-600 border-accent-600 text-white'
                                : 'bg-surface-2 border-line text-secondary hover:border-accent-400'"
                        >
                            <input
                                type="checkbox"
                                class="sr-only"
                                :checked="postTypeForm.taxonomyIds.includes(taxonomy.id)"
                                v-on:change="postTypeForm.taxonomyIds = toggleIn(postTypeForm.taxonomyIds, taxonomy.id)"
                            >
                            {{ taxonomy.slug }}
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="postTypeModal.open = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="postTypeModal.saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="fieldModal.open" max-width="lg" v-on:close="fieldModal.open = false">
            <h3 class="text-lg font-semibold text-primary">
                {{ fieldModal.editing ? t("admin.postTypes.fields.edit") : t("admin.postTypes.fields.add") }}
            </h3>
            <form class="space-y-4" v-on:submit.prevent="submitField">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppInput
                        v-model="fieldForm.name"
                        :label="t('admin.postTypes.fields.name')"
                        :error="fieldModal.errors.name ?? ''"
                        :placeholder="t('admin.postTypes.fields.namePlaceholder')"
                    />
                    <AppInput
                        v-model="fieldForm.label"
                        :label="t('admin.postTypes.fields.label')"
                        :error="fieldModal.errors.label ?? ''"
                        :placeholder="t('admin.postTypes.fields.labelPlaceholder')"
                    />
                </div>

                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t("admin.postTypes.fields.type") }}</label>
                    <AppSelect v-model="fieldForm.type">
                        <option v-for="fieldType in FIELD_TYPES" :key="fieldType" :value="fieldType">{{ fieldType }}</option>
                    </AppSelect>
                </div>

                <div v-if="fieldForm.type === 'select'">
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t("admin.postTypes.fields.selectChoices") }}</label>
                    <textarea
                        v-model="fieldForm.choicesText"
                        rows="5"
                        class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary placeholder-muted font-mono focus:border-accent-500 focus:ring-1 focus:ring-accent-500 transition resize-none"
                        :placeholder="t('admin.postTypes.fields.selectChoicesPlaceholder')"
                    />
                    <p class="text-xs text-muted mt-1">{{ t("admin.postTypes.fields.selectChoicesHint") }}</p>
                </div>

                <div v-if="fieldForm.type === 'reference'" class="space-y-2">
                    <AppMultiselect
                        v-model="fieldForm.referencePostTypeId"
                        :options="postTypes"
                        :label="t('admin.postTypes.fields.referenceTargetType')"
                        :placeholder="t('admin.postTypes.fields.referenceAnyType')"
                        :allow-empty="true"
                        track-by="id"
                        option-label="label"
                    />
                    <AppCheckbox v-model="fieldForm.referenceMultiple" :label="t('admin.postTypes.fields.referenceMultiple')" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppCheckbox v-model="fieldForm.required" :label="t('admin.postTypes.fields.required')" />
                    <AppCheckbox v-model="fieldForm.translatable" :label="t('admin.postTypes.fields.translatable')" />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="fieldModal.open = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="fieldModal.saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="!!deletingPostType" max-width="sm" v-on:close="deletingPostType = null">
            <p class="text-sm text-primary">{{ t("admin.postTypes.deleteConfirm", { label: deletingPostType?.label }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingPostType = null">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDeletePostType">{{ t("shared.common.delete") }}</AppButton>
            </div>
        </AppModal>

        <AppModal :show="!!deletingField" max-width="sm" v-on:close="deletingField = null">
            <p class="text-sm text-primary">{{ t("admin.postTypes.fields.deleteConfirm", { label: deletingField?.label }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingField = null">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDeleteField">{{ t("shared.common.delete") }}</AppButton>
            </div>
        </AppModal>
    </div>
</template>
