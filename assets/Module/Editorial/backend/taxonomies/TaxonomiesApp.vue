<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";
import { VueDraggable } from "vue-draggable-plus";
import { useTaxonomySelect } from "@editorial/backend/taxonomies/composables/useTaxonomySelect.js";
import { useTaxonomyTree } from "@editorial/backend/taxonomies/composables/useTaxonomyTree.js";
import { useTaxonomyDelete } from "@editorial/backend/taxonomies/composables/useTaxonomyDelete.js";
import { useTermDelete } from "@editorial/backend/taxonomies/composables/useTermDelete.js";
import { Plus, Pencil, Trash2, FolderTree, Folder, ChevronDown, ChevronRight, GripVertical, Lock, Save, X, Tag } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppCheckbox from "@/shared/components/form/AppCheckbox.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import TermNode from "@editorial/backend/taxonomies/TermNode.vue";
import { slugifyIfEmpty } from "@/shared/utils/format/slugify.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

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
const {
    modal: taxonomyModal, form: taxonomyForm,
    errors: taxonomyErrors, loading: taxonomyLoading,
    openCreate: openCreateTaxonomy, openEdit: openEditTaxonomy, submit: submitTaxonomy,
} = useFormModal({
    empty: () => ({
        slug: "", hierarchical: false,
        postTypeIds: props.postTypes.map((pt) => pt.id),
        translations: Object.fromEntries(props.locales.map((l) => [l, { label: "", description: "" }])),
    }),
    fromEntity: (tx) => ({
        slug: tx.slug, hierarchical: tx.hierarchical,
        postTypeIds: [...(tx.postTypeIds ?? [])],
        translations: Object.fromEntries(props.locales.map((l) => [l, {
            label: tx.translations?.[l]?.label ?? "",
            description: tx.translations?.[l]?.description ?? "",
        }])),
    }),
    createUrl: () => props.createPath,
    editUrl:   (tx) => buildPath(props.editPath, { id: tx.id }),
    onSuccess: ({ data }) => { replaceTaxonomy(data.taxonomy); selectedId.value = data.taxonomy.id; },
});

// ── Term form (modal) ────────────────────────────────────────────────────────
const pendingParentId = { value: null };

const {
    modal: termModal, form: termForm,
    errors: termErrors, loading: termLoading,
    openCreate: openTermCreate, openEdit: openEditTerm, submit: rawSubmitTerm,
} = useFormModal({
    empty: () => ({
        parentId: pendingParentId.value,
        translations: Object.fromEntries(props.locales.map((l) => [l, { name: "", slug: "", description: "" }])),
    }),
    fromEntity: (tr) => ({
        parentId: tr.parentId,
        translations: Object.fromEntries(props.locales.map((l) => [l, {
            name: tr.translations?.[l]?.name ?? "",
            slug: tr.translations?.[l]?.slug ?? "",
            description: tr.translations?.[l]?.description ?? "",
        }])),
    }),
    createUrl: () => buildPath(props.termCreatePath, { id: selected.value.id }),
    editUrl:   (tr) => buildPath(props.termEditPath, { id: selected.value.id, termId: tr.id }),
    onSuccess: ({ data }) => replaceTaxonomy(data.taxonomy),
});

function openCreateTerm(parentId = null) {
    pendingParentId.value = parentId;
    openTermCreate();
}

function autoSlugTerm(locale) {
    const entry = termForm.translations[locale];
    if (entry) entry.slug = slugifyIfEmpty(entry.slug, entry.name);
}

function submitTerm() {
    if (!selected.value) return;
    for (const locale of props.locales) {
        const entry = termForm.translations[locale];
        if (entry?.name) entry.slug = slugifyIfEmpty(entry.slug, entry.name);
    }
    rawSubmitTerm();
}

const parentOptions = computed(() => {
    if (!selected.value?.hierarchical) return [];
    const forbidden = termModal.entity
        ? collectDescendantIds(findNodeInTree(tree.value, termModal.entity.id) ?? termModal.entity)
        : new Set();
    return flatTermsForParentSelect.value.filter((opt) => !forbidden.has(opt.id));
});
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]">
        <aside class="lg:w-72 shrink-0 space-y-2">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("backend.taxonomies.title") }}</h2>
                <AppButton v-if="can('editorial.taxonomies.create')" variant="primary" size="md" v-on:click="openCreateTaxonomy">
                    <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("backend.taxonomies.addTaxonomy") }}
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
                    <Lock v-if="taxonomy.isBuiltIn" class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" :title="t('backend.taxonomies.builtIn')" />
                </button>
            </div>
        </aside>

        <main class="flex-1 min-w-0 space-y-4">
            <AppNoData v-if="!selected" :message="t('backend.taxonomies.empty')" />
            <div v-else class="space-y-4">
                <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-primary">{{ translationLabel(selected, activeLocale) }}</h3>
                            <p class="text-xs text-muted font-mono mt-0.5">{{ selected.slug }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <AppBadge v-if="selected.hierarchical" color="sky">
                                    <FolderTree class="w-3 h-3" :stroke-width="2" />
                                    {{ t("backend.taxonomies.hierarchical") }}
                                </AppBadge>
                                <AppBadge v-if="selected.isBuiltIn" color="amber">
                                    <Lock class="w-3 h-3" :stroke-width="2" />
                                    {{ t("backend.taxonomies.builtIn") }}
                                </AppBadge>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <AppButton v-if="can('editorial.taxonomies.edit')" variant="ghost" size="md" v-on:click="openEditTaxonomy(selected)">
                                <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("shared.common.edit") }}
                            </AppButton>
                            <AppButton
                                v-if="!selected.isBuiltIn && can('editorial.taxonomies.delete')"
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
                        <h4 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("backend.taxonomies.terms.title") }}</h4>
                        <div class="flex items-center gap-2">
                            <div v-if="locales.length > 1" class="flex gap-1">
                                <AppTab
                                    v-for="locale in locales"
                                    :key="locale"
                                    size="xs"
                                    :active="activeLocale === locale"
                                    active-class="bg-accent-600 text-white"
                                    v-on:click="activeLocale = locale"
                                >
                                    {{ locale.toUpperCase() }}
                                </AppTab>
                            </div>
                            <AppButton v-if="can('editorial.taxonomies.edit')" variant="primary" size="md" v-on:click="openCreateTerm()">
                                <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("backend.taxonomies.terms.addTerm") }}
                            </AppButton>
                        </div>
                    </div>

                    <AppMessage v-if="selected.hierarchical" variant="info">
                        {{ t("backend.taxonomies.terms.dndHint") }}
                    </AppMessage>

                    <AppNoData v-if="!tree.length" :message="t('backend.taxonomies.terms.empty')" />

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

        <AppModal
            :show="taxonomyModal.open"
            max-width="lg"
            :title="taxonomyModal.entity ? t('backend.taxonomies.editTaxonomy') : t('backend.taxonomies.addTaxonomy')"
            :icon="taxonomyModal.entity ? Pencil : Tag"
            :closeable="false"
            v-on:close="taxonomyModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitTaxonomy">
                <AppInput
                    v-model="taxonomyForm.slug"
                    :label="t('backend.taxonomies.slug')"
                    :error="taxonomyErrors.slug ?? ''"
                    :placeholder="t('backend.taxonomies.slugPlaceholder')"
                    :disabled="taxonomyModal.entity?.isBuiltIn ?? false"
                />

                <AppCheckbox
                    v-model="taxonomyForm.hierarchical"
                    :label="t('backend.taxonomies.hierarchical')"
                    :disabled="taxonomyModal.entity?.isBuiltIn ?? false"
                />

                <div class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t("backend.taxonomies.translations") }}</label>
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
                        </AppTab>
                    </div>
                    <AppInput
                        v-model="taxonomyForm.translations[activeLocale].label"
                        :label="t('backend.taxonomies.label')"
                        :placeholder="t('backend.taxonomies.labelPlaceholder')"
                    />
                    <AppTextarea
                        v-model="taxonomyForm.translations[activeLocale].description"
                        :label="t('backend.taxonomies.description')"
                        :placeholder="t('backend.taxonomies.descriptionPlaceholder')"
                        :rows="2"
                    />
                </div>

                <div class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t("backend.taxonomies.postTypes") }}</label>
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
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="taxonomyModal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="taxonomyLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="termModal.open"
            max-width="md"
            :title="termModal.entity ? t('backend.taxonomies.terms.editTerm') : t('backend.taxonomies.terms.addTerm')"
            :icon="termModal.entity ? Pencil : Tag"
            :closeable="false"
            v-on:close="termModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitTerm">
                <div v-if="selected?.hierarchical">
                    <AppMultiselect
                        v-model="termForm.parentId"
                        :options="parentOptions"
                        :label="t('backend.taxonomies.terms.parent')"
                        :placeholder="t('backend.taxonomies.terms.noParent')"
                        :allow-empty="true"
                        track-by="id"
                        option-label="label"
                    />
                </div>

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
                    </AppTab>
                </div>

                <AppInput
                    v-model="termForm.translations[activeLocale].name"
                    :label="t('backend.taxonomies.terms.name')"
                    :error="termErrors[`translations[${activeLocale}].name`] ?? ''"
                    :placeholder="t('backend.taxonomies.terms.namePlaceholder')"
                    v-on:blur="autoSlugTerm(activeLocale)"
                />
                <AppInput
                    v-model="termForm.translations[activeLocale].slug"
                    :label="t('backend.taxonomies.terms.slug')"
                    :error="termErrors[`translations[${activeLocale}].slug`] ?? ''"
                    :placeholder="t('backend.taxonomies.terms.slugPlaceholder')"
                />
                <AppTextarea
                    v-model="termForm.translations[activeLocale].description"
                    :label="t('backend.taxonomies.terms.description')"
                    :placeholder="t('backend.taxonomies.terms.descriptionPlaceholder')"
                    :rows="2"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="termModal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="termLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!deletingTaxonomy"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="deletingTaxonomy = null"
        >
            <p class="text-sm text-primary">{{ t("backend.taxonomies.deleteTaxonomyConfirm", { label: translationLabel(deletingTaxonomy, activeLocale) }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingTaxonomy = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDeleteTaxonomy"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!deletingTerm"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="deletingTerm = null"
        >
            <p class="text-sm text-primary">{{ t("backend.taxonomies.terms.deleteTermConfirm", { name: termName(deletingTerm, activeLocale) }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingTerm = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDeleteTerm"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
