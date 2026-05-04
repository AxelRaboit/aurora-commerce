<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";
import { VueDraggable } from "vue-draggable-plus";
import { useTaxonomySelect } from "@editorial/admin/taxonomies/composables/useTaxonomySelect.js";
import { useTaxonomyTree } from "@editorial/admin/taxonomies/composables/useTaxonomyTree.js";
import { useTaxonomyDelete } from "@editorial/admin/taxonomies/composables/useTaxonomyDelete.js";
import { useTermDelete } from "@editorial/admin/taxonomies/composables/useTermDelete.js";
import { Plus, Pencil, Trash2, FolderTree, Folder, ChevronDown, ChevronRight, GripVertical, Lock, Save, } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppCheckbox from "@/shared/components/form/AppCheckbox.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import TermNode from "@editorial/admin/taxonomies/TermNode.vue";
import { slugifyIfEmpty } from "@/shared/utils/format/slugify.js";

const { t } = useI18n();

const props = defineProps({
    taxonomies: { type: Array, default: () => [] },
    postTypes: { type: Array, default: () => [] },
    locales: { type: Array, default: () => ["fr"] },
    createPath: { type: String, required: true },
    editPath: { type: String, required: true },
    deletePath: { type: String, required: true },
    termCreatePath: { type: String, required: true },
    termEditPath: { type: String, required: true },
    termDeletePath: { type: String, required: true },
    termReorderPath: { type: String, required: true },
});

const { taxonomies, selectedId, activeLocale, selected, replaceTaxonomy, translationLabel, termName } =
    useTaxonomySelect(props.taxonomies, props.locales);

const flatTerms = computed(() => selected.value?.terms ?? []);

const { tree, dragging, collapsed, toggleCollapsed, onDragEnd, flatTermsForParentSelect, collectDescendantIds, findNodeInTree } =
    useTaxonomyTree(selected, flatTerms, props.termReorderPath, props.locales, activeLocale, replaceTaxonomy, termName);

const { deletingTaxonomy, confirmDeleteTaxonomy } = useTaxonomyDelete(props.deletePath, taxonomies, selectedId);
const { deletingTerm, confirmDeleteTerm } = useTermDelete(props.termDeletePath, selected, replaceTaxonomy);

// ── Taxonomy form (modal) ────────────────────────────────────────────────────
const { modal: taxonomyModal, openCreate: taxonomyModalCreate, openEdit: taxonomyModalEdit, submit: taxonomyModalSubmit } = useFormModal();
const taxonomyForm = reactive({ slug: "", hierarchical: false, translations: {}, postTypeIds: [] });

function openCreateTaxonomy() {
    taxonomyModalCreate(() => Object.assign(taxonomyForm, {
        slug: "", hierarchical: false,
        postTypeIds: props.postTypes.map((pt) => pt.id),
        translations: Object.fromEntries(props.locales.map((l) => [l, { label: "", description: "" }])),
    }));
}

function openEditTaxonomy(taxonomy) {
    taxonomyModalEdit(taxonomy, (tx) => Object.assign(taxonomyForm, {
        slug: tx.slug, hierarchical: tx.hierarchical, postTypeIds: [...(tx.postTypeIds ?? [])],
        translations: Object.fromEntries(props.locales.map((l) => [l, {
            label: tx.translations?.[l]?.label ?? "",
            description: tx.translations?.[l]?.description ?? "",
        }])),
    }));
}

async function submitTaxonomy() {
    const url = taxonomyModal.editing
        ? buildPath(props.editPath, { id: taxonomyModal.editing.id })
        : props.createPath;
    await taxonomyModalSubmit(url, taxonomyForm, (data) => {
        replaceTaxonomy(data.taxonomy);
        selectedId.value = data.taxonomy.id;
    });
}


// ── Term form (modal) ────────────────────────────────────────────────────────
const { modal: termModal, openCreate: termModalCreate, openEdit: termModalEdit, submit: termModalSubmit } = useFormModal();
const termForm = reactive({ parentId: null, translations: {} });

function openCreateTerm(parentId = null) {
    termModalCreate(() => Object.assign(termForm, {
        parentId,
        translations: Object.fromEntries(props.locales.map((l) => [l, { name: "", slug: "", description: "" }])),
    }));
}

function openEditTerm(term) {
    termModalEdit(term, (tr) => Object.assign(termForm, {
        parentId: tr.parentId,
        translations: Object.fromEntries(props.locales.map((l) => [l, {
            name: tr.translations?.[l]?.name ?? "",
            slug: tr.translations?.[l]?.slug ?? "",
            description: tr.translations?.[l]?.description ?? "",
        }])),
    }));
}

function autoSlugTerm(locale) {
    const entry = termForm.translations[locale];
    if (entry) entry.slug = slugifyIfEmpty(entry.slug, entry.name);
}

async function submitTerm() {
    if (!selected.value) return;
    for (const locale of props.locales) {
        const entry = termForm.translations[locale];
        if (entry?.name) entry.slug = slugifyIfEmpty(entry.slug, entry.name);
    }
    const url = termModal.editing
        ? buildPath(props.termEditPath, { id: selected.value.id, termId: termModal.editing.id })
        : buildPath(props.termCreatePath, { id: selected.value.id });
    await termModalSubmit(url, termForm, (data) => replaceTaxonomy(data.taxonomy));
}


const parentOptions = computed(() => {
    if (!selected.value?.hierarchical) return [];
    const forbidden = termModal.editing ? collectDescendantIds(findNodeInTree(tree.value, termModal.editing.id) ?? termModal.editing) : new Set();
    return flatTermsForParentSelect.value.filter((opt) => !forbidden.has(opt.id));
});
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]">
        <aside class="lg:w-72 shrink-0 space-y-2">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("admin.taxonomies.title") }}</h2>
                <AppButton variant="primary" size="md" v-on:click="openCreateTaxonomy">
                    <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("admin.taxonomies.addTaxonomy") }}
                </AppButton>
            </div>
            <div class="space-y-1">
                <button
                    v-for="taxonomy in taxonomies"
                    :key="taxonomy.id"
                    type="button"
                    class="w-full text-left px-3 py-2 rounded-lg transition-colors flex items-center gap-2"
                    :class="selectedId === taxonomy.id
                        ? 'bg-accent-600/15 text-accent-400 border border-accent-600/30'
                        : 'bg-surface hover:bg-surface-2 text-primary border border-line/60'"
                    v-on:click="selectedId = taxonomy.id"
                >
                    <FolderTree v-if="taxonomy.hierarchical" class="w-4 h-4 shrink-0" :stroke-width="2" />
                    <Folder v-else class="w-4 h-4 shrink-0" :stroke-width="2" />
                    <span class="flex-1 text-sm font-medium truncate">{{ translationLabel(taxonomy, activeLocale) }}</span>
                    <Lock v-if="taxonomy.isBuiltIn" class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" :title="t('admin.taxonomies.builtIn')" />
                </button>
            </div>
        </aside>

        <main class="flex-1 min-w-0 space-y-4">
            <AppNoData v-if="!selected" :message="t('admin.taxonomies.empty')" />
            <div v-else class="space-y-4">
                <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-primary">{{ translationLabel(selected, activeLocale) }}</h3>
                            <p class="text-xs text-muted font-mono mt-0.5">{{ selected.slug }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <AppBadge v-if="selected.hierarchical" color="sky">
                                    <FolderTree class="w-3 h-3" :stroke-width="2" />
                                    {{ t("admin.taxonomies.hierarchical") }}
                                </AppBadge>
                                <AppBadge v-if="selected.isBuiltIn" color="amber">
                                    <Lock class="w-3 h-3" :stroke-width="2" />
                                    {{ t("admin.taxonomies.builtIn") }}
                                </AppBadge>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <AppButton variant="ghost" size="md" v-on:click="openEditTaxonomy(selected)">
                                <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("shared.common.edit") }}
                            </AppButton>
                            <AppButton
                                v-if="!selected.isBuiltIn"
                                variant="danger"
                                size="md"
                                v-on:click="deletingTaxonomy = selected"
                            >
                                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("shared.common.delete") }}
                            </AppButton>
                        </div>
                    </div>
                </div>

                <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <h4 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("admin.taxonomies.terms.title") }}</h4>
                        <div class="flex items-center gap-2">
                            <div v-if="locales.length > 1" class="flex gap-1">
                                <button
                                    v-for="locale in locales"
                                    :key="locale"
                                    type="button"
                                    class="px-2 py-0.5 text-xs font-medium rounded transition-colors"
                                    :class="activeLocale === locale
                                        ? 'bg-accent-600 text-white'
                                        : 'text-secondary hover:bg-surface-2'"
                                    v-on:click="activeLocale = locale"
                                >
                                    {{ locale.toUpperCase() }}
                                </button>
                            </div>
                            <AppButton variant="primary" size="md" v-on:click="openCreateTerm()">
                                <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("admin.taxonomies.terms.addTerm") }}
                            </AppButton>
                        </div>
                    </div>

                    <AppMessage v-if="selected.hierarchical" variant="info">
                        {{ t("admin.taxonomies.terms.dndHint") }}
                    </AppMessage>

                    <AppNoData v-if="!tree.length" :message="t('admin.taxonomies.terms.empty')" />

                    <VueDraggable
                        v-else
                        v-model="tree"
                        :group="{ name: `taxonomy-${selected.id}`, pull: true, put: true }"
                        handle=".drag-handle"
                        :animation="150"
                        ghost-class="opacity-50"
                        class="space-y-1"
                        v-on:start="dragging = true"
                        v-on:end="onDragEnd"
                    >
                        <template v-for="node in tree" :key="node.id">
                            <TermNode
                                :node="node"
                                :hierarchical="selected.hierarchical"
                                :active-locale="activeLocale"
                                :group-name="`taxonomy-${selected.id}`"
                                :collapsed="collapsed"
                                v-on:toggle-collapse="toggleCollapsed($event)"
                                v-on:edit="openEditTerm($event)"
                                v-on:delete="deletingTerm = $event"
                                v-on:add-child="openCreateTerm($event)"
                                v-on:end="onDragEnd"
                            />
                        </template>
                    </VueDraggable>
                </div>
            </div>
        </main>

        <AppModal :show="taxonomyModal.open" max-width="lg" v-on:close="taxonomyModal.open = false">
            <h3 class="text-lg font-semibold text-primary">
                {{ taxonomyModal.editing ? t("admin.taxonomies.editTaxonomy") : t("admin.taxonomies.addTaxonomy") }}
            </h3>
            <form class="space-y-4" v-on:submit.prevent="submitTaxonomy">
                <AppInput
                    v-model="taxonomyForm.slug"
                    :label="t('admin.taxonomies.slug')"
                    :error="taxonomyModal.errors.slug ?? ''"
                    :placeholder="t('admin.taxonomies.slugPlaceholder')"
                    :disabled="taxonomyModal.editing?.isBuiltIn ?? false"
                />

                <AppCheckbox
                    v-model="taxonomyForm.hierarchical"
                    :label="t('admin.taxonomies.hierarchical')"
                    :disabled="taxonomyModal.editing?.isBuiltIn ?? false"
                />

                <div class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t("admin.taxonomies.translations") }}</label>
                    <div class="flex gap-1">
                        <button
                            v-for="locale in locales"
                            :key="locale"
                            type="button"
                            class="px-2 py-0.5 text-xs font-medium rounded transition-colors"
                            :class="activeLocale === locale ? 'bg-accent-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                            v-on:click="activeLocale = locale"
                        >
                            {{ locale.toUpperCase() }}
                        </button>
                    </div>
                    <AppInput
                        v-model="taxonomyForm.translations[activeLocale].label"
                        :label="t('admin.taxonomies.label')"
                        :placeholder="t('admin.taxonomies.labelPlaceholder')"
                    />
                    <AppTextarea
                        v-model="taxonomyForm.translations[activeLocale].description"
                        :label="t('admin.taxonomies.description')"
                        :placeholder="t('admin.taxonomies.descriptionPlaceholder')"
                        :rows="2"
                    />
                </div>

                <div class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t("admin.taxonomies.postTypes") }}</label>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="pt in postTypes"
                            :key="pt.id"
                            class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium cursor-pointer border transition-colors"
                            :class="taxonomyForm.postTypeIds.includes(pt.id)
                                ? 'bg-accent-600 border-accent-600 text-white'
                                : 'bg-surface-2 border-line text-secondary hover:border-accent-400'"
                        >
                            <input
                                type="checkbox"
                                class="sr-only"
                                :checked="taxonomyForm.postTypeIds.includes(pt.id)"
                                v-on:change="taxonomyForm.postTypeIds.includes(pt.id)
                                    ? taxonomyForm.postTypeIds = taxonomyForm.postTypeIds.filter((id) => id !== pt.id)
                                    : taxonomyForm.postTypeIds.push(pt.id)"
                            >
                            {{ pt.label }}
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="taxonomyModal.open = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="taxonomyModal.saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="termModal.open" max-width="md" v-on:close="termModal.open = false">
            <h3 class="text-lg font-semibold text-primary">
                {{ termModal.editing ? t("admin.taxonomies.terms.editTerm") : t("admin.taxonomies.terms.addTerm") }}
            </h3>
            <form class="space-y-4" v-on:submit.prevent="submitTerm">
                <div v-if="selected?.hierarchical">
                    <AppMultiselect
                        v-model="termForm.parentId"
                        :options="parentOptions"
                        :label="t('admin.taxonomies.terms.parent')"
                        :placeholder="t('admin.taxonomies.terms.noParent')"
                        :allow-empty="true"
                        track-by="id"
                        option-label="label"
                    />
                </div>

                <div class="flex gap-1">
                    <button
                        v-for="locale in locales"
                        :key="locale"
                        type="button"
                        class="px-2 py-0.5 text-xs font-medium rounded transition-colors"
                        :class="activeLocale === locale ? 'bg-accent-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                        v-on:click="activeLocale = locale"
                    >
                        {{ locale.toUpperCase() }}
                    </button>
                </div>

                <AppInput
                    v-model="termForm.translations[activeLocale].name"
                    :label="t('admin.taxonomies.terms.name')"
                    :error="termModal.errors[`translations[${activeLocale}].name`] ?? ''"
                    :placeholder="t('admin.taxonomies.terms.namePlaceholder')"
                    v-on:blur="autoSlugTerm(activeLocale)"
                />
                <AppInput
                    v-model="termForm.translations[activeLocale].slug"
                    :label="t('admin.taxonomies.terms.slug')"
                    :error="termModal.errors[`translations[${activeLocale}].slug`] ?? ''"
                    :placeholder="t('admin.taxonomies.terms.slugPlaceholder')"
                />
                <AppTextarea
                    v-model="termForm.translations[activeLocale].description"
                    :label="t('admin.taxonomies.terms.description')"
                    :placeholder="t('admin.taxonomies.terms.descriptionPlaceholder')"
                    :rows="2"
                />

                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="termModal.open = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="termModal.saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="!!deletingTaxonomy" max-width="sm" v-on:close="deletingTaxonomy = null">
            <p class="text-sm text-primary">{{ t("admin.taxonomies.deleteTaxonomyConfirm", { label: translationLabel(deletingTaxonomy, activeLocale) }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingTaxonomy = null">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDeleteTaxonomy">{{ t("shared.common.delete") }}</AppButton>
            </div>
        </AppModal>

        <AppModal :show="!!deletingTerm" max-width="sm" v-on:close="deletingTerm = null">
            <p class="text-sm text-primary">{{ t("admin.taxonomies.terms.deleteTermConfirm", { name: termName(deletingTerm, activeLocale) }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingTerm = null">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDeleteTerm">{{ t("shared.common.delete") }}</AppButton>
            </div>
        </AppModal>
    </div>
</template>
