<script setup>
import { ref, computed, reactive, watch, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { VueDraggable } from "vue-draggable-plus";
import { Plus, Pencil, Trash2, FolderTree, Folder, ChevronDown, ChevronRight, GripVertical, Lock } from "lucide-vue-next";
import AppButton from "@/components/AppButton.vue";
import AppIconButton from "@/components/AppIconButton.vue";
import AppInput from "@/components/AppInput.vue";
import AppTextarea from "@/components/AppTextarea.vue";
import AppSelect from "@/components/AppSelect.vue";
import AppCheckbox from "@/components/AppCheckbox.vue";
import AppModal from "@/components/AppModal.vue";
import AppMessage from "@/components/AppMessage.vue";
import AppNoData from "@/components/AppNoData.vue";
import TermNode from "@/admin/taxonomies/TermNode.vue";
import { slugify } from "@/utils/slugify.js";

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

const taxonomies = ref([...props.taxonomies]);
const selectedId = ref(taxonomies.value[0]?.id ?? null);
const activeLocale = ref(props.locales[0] ?? "fr");

const selected = computed(() => taxonomies.value.find((tx) => tx.id === selectedId.value) ?? null);

function replaceTaxonomy(fresh) {
    const idx = taxonomies.value.findIndex((tx) => tx.id === fresh.id);
    if (idx === -1) taxonomies.value.push(fresh);
    else taxonomies.value[idx] = fresh;
}

function translationLabel(taxonomy, locale) {
    return taxonomy?.translations?.[locale]?.label
        ?? taxonomy?.translations?.[props.locales[0]]?.label
        ?? taxonomy?.slug
        ?? "";
}

function termName(term, locale) {
    return term?.translations?.[locale]?.name
        ?? term?.translations?.[props.locales[0]]?.name
        ?? "(—)";
}

// ── Tree building for the selected taxonomy ──────────────────────────────────
const flatTerms = computed(() => selected.value?.terms ?? []);

function buildTree(terms) {
    const byId = new Map(terms.map((term) => [term.id, { ...term, children: [] }]));
    const roots = [];
    for (const node of byId.values()) {
        if (node.parentId && byId.has(node.parentId)) {
            byId.get(node.parentId).children.push(node);
        } else {
            roots.push(node);
        }
    }
    const sortRecursive = (nodes) => {
        nodes.sort((a, b) => (a.position - b.position) || (a.id - b.id));
        nodes.forEach((n) => sortRecursive(n.children));
    };
    sortRecursive(roots);
    return roots;
}

const tree = ref([]);

watch(
    () => selected.value?.id,
    () => {
        tree.value = buildTree(flatTerms.value);
    },
    { immediate: true },
);

watch(flatTerms, (terms) => {
    tree.value = buildTree(terms);
}, { deep: true });

// ── Taxonomy form (modal) ────────────────────────────────────────────────────
const taxonomyModal = reactive({ open: false, editing: null, errors: {}, saving: false });
const taxonomyForm = reactive({ slug: "", hierarchical: false, translations: {}, postTypeIds: [] });

function openCreateTaxonomy() {
    taxonomyModal.editing = null;
    taxonomyModal.errors = {};
    taxonomyForm.slug = "";
    taxonomyForm.hierarchical = false;
    taxonomyForm.postTypeIds = props.postTypes.map((pt) => pt.id);
    taxonomyForm.translations = Object.fromEntries(props.locales.map((l) => [l, { label: "", description: "" }]));
    taxonomyModal.open = true;
}

function openEditTaxonomy(taxonomy) {
    taxonomyModal.editing = taxonomy;
    taxonomyModal.errors = {};
    taxonomyForm.slug = taxonomy.slug;
    taxonomyForm.hierarchical = taxonomy.hierarchical;
    taxonomyForm.postTypeIds = [...(taxonomy.postTypeIds ?? [])];
    taxonomyForm.translations = Object.fromEntries(
        props.locales.map((l) => [l, {
            label: taxonomy.translations?.[l]?.label ?? "",
            description: taxonomy.translations?.[l]?.description ?? "",
        }]),
    );
    taxonomyModal.open = true;
}

async function submitTaxonomy() {
    taxonomyModal.saving = true;
    taxonomyModal.errors = {};
    try {
        const url = taxonomyModal.editing
            ? props.editPath.replace("__id__", taxonomyModal.editing.id)
            : props.createPath;
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(taxonomyForm),
        });
        const data = await response.json();
        if (!data.success) {
            taxonomyModal.errors = data.errors ?? {};
            return;
        }
        replaceTaxonomy(data.taxonomy);
        selectedId.value = data.taxonomy.id;
        taxonomyModal.open = false;
        toast.success(t("common.saved"));
    } catch {
        toast.error(t("common.error"));
    } finally {
        taxonomyModal.saving = false;
    }
}

const deletingTaxonomy = ref(null);
async function confirmDeleteTaxonomy() {
    const taxonomy = deletingTaxonomy.value;
    if (!taxonomy) return;
    try {
        const response = await fetch(props.deletePath.replace("__id__", taxonomy.id), { method: "POST" });
        const data = await response.json();
        if (!data.success) {
            toast.error(data.error ?? t("common.error"));
            return;
        }
        taxonomies.value = taxonomies.value.filter((tx) => tx.id !== taxonomy.id);
        if (selectedId.value === taxonomy.id) {
            selectedId.value = taxonomies.value[0]?.id ?? null;
        }
        toast.success(t("common.deleted"));
    } catch {
        toast.error(t("common.error"));
    } finally {
        deletingTaxonomy.value = null;
    }
}

// ── Term form (modal) ────────────────────────────────────────────────────────
const termModal = reactive({ open: false, editing: null, errors: {}, saving: false });
const termForm = reactive({ parentId: null, translations: {} });

function openCreateTerm(parentId = null) {
    termModal.editing = null;
    termModal.errors = {};
    termForm.parentId = parentId;
    termForm.translations = Object.fromEntries(props.locales.map((l) => [l, { name: "", slug: "", description: "" }]));
    termModal.open = true;
}

function openEditTerm(term) {
    termModal.editing = term;
    termModal.errors = {};
    termForm.parentId = term.parentId;
    termForm.translations = Object.fromEntries(props.locales.map((l) => [l, {
        name: term.translations?.[l]?.name ?? "",
        slug: term.translations?.[l]?.slug ?? "",
        description: term.translations?.[l]?.description ?? "",
    }]));
    termModal.open = true;
}

function autoSlugTerm(locale) {
    const entry = termForm.translations[locale];
    if (!entry) return;
    if (!entry.slug) entry.slug = slugify(entry.name);
}

async function submitTerm() {
    if (!selected.value) return;
    termModal.saving = true;
    termModal.errors = {};

    for (const locale of props.locales) {
        const entry = termForm.translations[locale];
        if (entry?.name && !entry.slug) entry.slug = slugify(entry.name);
    }

    try {
        const url = termModal.editing
            ? props.termEditPath.replace("__id__", selected.value.id).replace("__termId__", termModal.editing.id)
            : props.termCreatePath.replace("__id__", selected.value.id);
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(termForm),
        });
        const data = await response.json();
        if (!data.success) {
            termModal.errors = data.errors ?? {};
            return;
        }
        replaceTaxonomy(data.taxonomy);
        termModal.open = false;
        toast.success(t("common.saved"));
    } catch {
        toast.error(t("common.error"));
    } finally {
        termModal.saving = false;
    }
}

const deletingTerm = ref(null);
async function confirmDeleteTerm() {
    const term = deletingTerm.value;
    if (!term || !selected.value) return;
    try {
        const url = props.termDeletePath
            .replace("__id__", selected.value.id)
            .replace("__termId__", term.id);
        const response = await fetch(url, { method: "POST" });
        const data = await response.json();
        if (!data.success) {
            toast.error(t("common.error"));
            return;
        }
        replaceTaxonomy(data.taxonomy);
        toast.success(t("common.deleted"));
    } catch {
        toast.error(t("common.error"));
    } finally {
        deletingTerm.value = null;
    }
}

// ── Drag & drop ──────────────────────────────────────────────────────────────
const dragging = ref(false);

function flattenTreeForReorder(nodes, parentId = null) {
    const entries = [];
    nodes.forEach((node, index) => {
        entries.push({ id: node.id, parentId, position: index });
        if (node.children?.length) {
            entries.push(...flattenTreeForReorder(node.children, node.id));
        }
    });
    return entries;
}

async function persistTreeOrder() {
    if (!selected.value) return;
    const entries = flattenTreeForReorder(tree.value);
    try {
        const response = await fetch(props.termReorderPath.replace("__id__", selected.value.id), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ entries }),
        });
        const data = await response.json();
        if (!data.success) {
            toast.error(data.error ?? t("common.error"));
            return;
        }
        replaceTaxonomy(data.taxonomy);
    } catch {
        toast.error(t("common.error"));
    }
}

function onDragEnd() {
    dragging.value = false;
    nextTick(() => persistTreeOrder());
}

// ── Collapsed nodes state ────────────────────────────────────────────────────
const collapsed = reactive(new Set());
function toggleCollapsed(id) {
    if (collapsed.has(id)) collapsed.delete(id);
    else collapsed.add(id);
}

const flatTermsForParentSelect = computed(() => {
    if (!selected.value?.hierarchical) return [];
    const list = [];
    const walk = (nodes, depth) => {
        nodes.forEach((n) => {
            list.push({ id: n.id, label: `${"— ".repeat(depth)}${termName(n, activeLocale.value)}`, descendants: collectDescendantIds(n) });
            if (n.children) walk(n.children, depth + 1);
        });
    };
    walk(tree.value, 0);
    return list;
});

function collectDescendantIds(node) {
    const ids = new Set([node.id]);
    for (const child of node.children ?? []) {
        collectDescendantIds(child).forEach((id) => ids.add(id));
    }
    return ids;
}

const parentOptions = computed(() => {
    if (!selected.value?.hierarchical) return [];
    const forbidden = termModal.editing ? collectDescendantIds(findNodeInTree(tree.value, termModal.editing.id) ?? termModal.editing) : new Set();
    return flatTermsForParentSelect.value.filter((opt) => !forbidden.has(opt.id));
});

function findNodeInTree(nodes, id) {
    for (const n of nodes) {
        if (n.id === id) return n;
        const found = findNodeInTree(n.children ?? [], id);
        if (found) return found;
    }
    return null;
}
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]">
        <!-- Sidebar: taxonomies list -->
        <aside class="lg:w-72 shrink-0 space-y-2">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("admin.taxonomies.title") }}</h2>
                <AppButton variant="primary" size="sm" v-on:click="openCreateTaxonomy">
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
                        ? 'bg-indigo-600/15 text-indigo-400 border border-indigo-600/30'
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

        <!-- Main: selected taxonomy details -->
        <main class="flex-1 min-w-0 space-y-4">
            <AppNoData v-if="!selected" :message="t('admin.taxonomies.empty')" />
            <div v-else class="space-y-4">
                <!-- Taxonomy header -->
                <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-primary">{{ translationLabel(selected, activeLocale) }}</h3>
                            <p class="text-xs text-muted font-mono mt-0.5">{{ selected.slug }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span v-if="selected.hierarchical" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-sky-500/15 text-sky-400">
                                    <FolderTree class="w-3 h-3" :stroke-width="2" />
                                    {{ t("admin.taxonomies.hierarchical") }}
                                </span>
                                <span v-if="selected.isBuiltIn" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-amber-500/15 text-amber-400">
                                    <Lock class="w-3 h-3" :stroke-width="2" />
                                    {{ t("admin.taxonomies.builtIn") }}
                                </span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <AppButton variant="secondary" size="sm" v-on:click="openEditTaxonomy(selected)">
                                <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("common.edit") }}
                            </AppButton>
                            <AppButton v-if="!selected.isBuiltIn" variant="danger" size="sm" v-on:click="deletingTaxonomy = selected">
                                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("common.delete") }}
                            </AppButton>
                        </div>
                    </div>
                </div>

                <!-- Terms -->
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
                                        ? 'bg-indigo-600 text-white'
                                        : 'text-secondary hover:bg-surface-2'"
                                    v-on:click="activeLocale = locale"
                                >
                                    {{ locale.toUpperCase() }}
                                </button>
                            </div>
                            <AppButton variant="primary" size="sm" v-on:click="openCreateTerm()">
                                <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("admin.taxonomies.terms.addTerm") }}
                            </AppButton>
                        </div>
                    </div>

                    <AppMessage v-if="selected.hierarchical" variant="info">
                        {{ t("admin.taxonomies.terms.dndHint") }}
                    </AppMessage>

                    <AppNoData v-if="!tree.length" :message="t('admin.taxonomies.terms.empty')" />

                    <!-- Tree DnD -->
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

        <!-- Taxonomy modal -->
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
                            :class="activeLocale === locale ? 'bg-indigo-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                            v-on:click="activeLocale = locale"
                        >
                            {{ locale.toUpperCase() }}
                        </button>
                    </div>
                    <AppInput
                        v-model="taxonomyForm.translations[activeLocale].label"
                        :label="t('admin.taxonomies.label')"
                    />
                    <AppTextarea
                        v-model="taxonomyForm.translations[activeLocale].description"
                        :label="t('admin.taxonomies.description')"
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
                                ? 'bg-indigo-600 border-indigo-600 text-white'
                                : 'bg-surface-2 border-line text-secondary hover:border-indigo-400'"
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
                    <AppButton variant="ghost" size="md" v-on:click="taxonomyModal.open = false">{{ t("common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="taxonomyModal.saving">{{ t("common.save") }}</AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Term modal -->
        <AppModal :show="termModal.open" max-width="md" v-on:close="termModal.open = false">
            <h3 class="text-lg font-semibold text-primary">
                {{ termModal.editing ? t("admin.taxonomies.terms.editTerm") : t("admin.taxonomies.terms.addTerm") }}
            </h3>
            <form class="space-y-4" v-on:submit.prevent="submitTerm">
                <div v-if="selected?.hierarchical">
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t("admin.taxonomies.terms.parent") }}</label>
                    <AppSelect v-model="termForm.parentId">
                        <option :value="null">{{ t("admin.taxonomies.terms.noParent") }}</option>
                        <option v-for="opt in parentOptions" :key="opt.id" :value="opt.id">
                            {{ opt.label }}
                        </option>
                    </AppSelect>
                </div>

                <div class="flex gap-1">
                    <button
                        v-for="locale in locales"
                        :key="locale"
                        type="button"
                        class="px-2 py-0.5 text-xs font-medium rounded transition-colors"
                        :class="activeLocale === locale ? 'bg-indigo-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                        v-on:click="activeLocale = locale"
                    >
                        {{ locale.toUpperCase() }}
                    </button>
                </div>

                <AppInput
                    v-model="termForm.translations[activeLocale].name"
                    :label="t('admin.taxonomies.terms.name')"
                    :error="termModal.errors[`translations[${activeLocale}].name`] ?? ''"
                    v-on:blur="autoSlugTerm(activeLocale)"
                />
                <AppInput
                    v-model="termForm.translations[activeLocale].slug"
                    :label="t('admin.taxonomies.terms.slug')"
                    :error="termModal.errors[`translations[${activeLocale}].slug`] ?? ''"
                />
                <AppTextarea
                    v-model="termForm.translations[activeLocale].description"
                    :label="t('admin.taxonomies.terms.description')"
                    :rows="2"
                />

                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="termModal.open = false">{{ t("common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="termModal.saving">{{ t("common.save") }}</AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Delete taxonomy confirm -->
        <AppModal :show="!!deletingTaxonomy" max-width="sm" v-on:close="deletingTaxonomy = null">
            <p class="text-sm text-primary">{{ t("admin.taxonomies.deleteTaxonomyConfirm", { label: translationLabel(deletingTaxonomy, activeLocale) }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingTaxonomy = null">{{ t("common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDeleteTaxonomy">{{ t("common.delete") }}</AppButton>
            </div>
        </AppModal>

        <!-- Delete term confirm -->
        <AppModal :show="!!deletingTerm" max-width="sm" v-on:close="deletingTerm = null">
            <p class="text-sm text-primary">{{ t("admin.taxonomies.terms.deleteTermConfirm", { name: termName(deletingTerm, activeLocale) }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingTerm = null">{{ t("common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDeleteTerm">{{ t("common.delete") }}</AppButton>
            </div>
        </AppModal>
    </div>
</template>
