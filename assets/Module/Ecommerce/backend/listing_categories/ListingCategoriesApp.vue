<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { VueDraggable } from "vue-draggable-plus";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { useListingCategoriesForm } from "@ecommerce/backend/listing_categories/composables/useListingCategoriesForm.js";
import { slugifyIfEmpty } from "@/shared/utils/format/slugify.js";
import ListingCategoryNode from "@ecommerce/backend/listing_categories/ListingCategoryNode.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppToggle from "@/shared/components/form/AppToggle.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppImagePickerField from "@/shared/components/form/AppImagePickerField.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { Trash2, Plus, Save, X, FolderTree } from "lucide-vue-next";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();
const { request } = useRequest();

const props = defineProps({
    categories: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    reorderPath: { type: String, required: true },
    extraFields: { type: Object, default: () => ({}) },
});

const items = ref([...props.categories]);
const activeTab = ref(props.locales[0]?.code ?? "en");
const activeLocale = computed(() => activeTab.value);
const collapsed = ref(new Set());

function toggleCollapsed(id) {
    if (collapsed.value.has(id)) collapsed.value.delete(id);
    else collapsed.value.add(id);
    // Force reactivity on Set
    collapsed.value = new Set(collapsed.value);
}

function buildTree(flatCategories) {
    const byId = new Map(
        flatCategories.map((category) => [category.id, { ...category, children: [] }]),
    );
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
        nodes.forEach((node) => sortRecursive(node.children));
    };
    sortRecursive(roots);
    return roots;
}

const tree = ref(buildTree(items.value));

watch(items, (next) => { tree.value = buildTree(next); }, { deep: true });

async function reload() {
    const response = await fetch(props.listPath, { headers: { Accept: "application/json" } });
    const json = await response.json();
    items.value = json.items ?? [];
}

function collectDescendantIds(node, set = new Set()) {
    if (!node) return set;
    set.add(node.id);
    for (const child of node.children ?? []) collectDescendantIds(child, set);
    return set;
}

function findNodeInTree(nodes, id) {
    for (const node of nodes) {
        if (node.id === id) return node;
        const found = findNodeInTree(node.children ?? [], id);
        if (found) return found;
    }
    return null;
}

const parentOptions = computed(() => {
    const forbidden = editingCategory.value
        ? collectDescendantIds(findNodeInTree(tree.value, editingCategory.value.id) ?? editingCategory.value)
        : new Set();
    const list = [];
    const walk = (nodes, depth) => {
        for (const node of nodes) {
            if (forbidden.has(node.id)) continue;
            const translation = node.translations?.[activeLocale.value];
            const firstTranslation = Object.values(node.translations ?? {})[0];
            const name = translation?.name ?? firstTranslation?.name ?? `#${node.id}`;
            list.push({ id: node.id, label: `${"— ".repeat(depth)}${name}` });
            if (node.children?.length) walk(node.children, depth + 1);
        }
    };
    walk(tree.value, 0);
    return list;
});

function autoSlug(locale) {
    const entry = editForm.translations?.[locale];
    if (entry) entry.slug = slugifyIfEmpty(entry.slug, entry.name);
}

const {
    showCreate,
    showEdit,
    editingCategory,
    editForm,
    formImage,
    createErrors,
    createLoading,
    editErrors,
    editLoading,
    openCreate,
    openEdit,
    submitCreate,
    submitEdit,
} = useListingCategoriesForm({
    createPath: props.createPath,
    updatePath: props.updatePath,
    locales: props.locales,
    reset: reload,
    extraFields: props.extraFields,
});

function openCreateRoot() {
    openCreate();
}

function openCreateChild(parentId) {
    openCreate();
    editForm.parentId = parentId;
}

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath,
    reload,
    "backend.ecommerce.listing_categories.deleted",
);

function displayName(category) {
    if (!category) return "";
    const translation = category.translations?.[activeLocale.value];
    if (translation?.name) return translation.name;
    const firstTranslation = Object.values(category.translations ?? {})[0];
    return firstTranslation?.name ?? `#${category.id}`;
}

// ── Drag & drop persistence ──────────────────────────────────────────────────
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

let reorderTimer = null;
async function persistTreeOrder() {
    const entries = flattenTreeForReorder(tree.value);
    const data = await request(props.reorderPath, { entries });
    if (!data) return;
    if (!data.success) {
        toast.error(data.error ?? t("shared.common.error"));
        await reload();
        return;
    }
    if (Array.isArray(data.items)) {
        items.value = data.items;
    }
}

function onDragEnd() {
    if (reorderTimer) clearTimeout(reorderTimer);
    reorderTimer = setTimeout(() => {
        nextTick(() => persistTreeOrder());
    }, 300);
}

defineSlots();
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <div v-if="locales.length > 1" class="flex gap-1">
                <AppTab
                    v-for="locale in locales"
                    :key="locale.code"
                    size="xs"
                    :active="activeTab === locale.code"
                    active-class="bg-accent-600 text-white"
                    inactive-class="bg-surface-2 text-secondary hover:bg-surface-3"
                    v-on:click="activeTab = locale.code"
                >
                    {{ locale.label }}
                </AppTab>
            </div>
            <AppButton
                v-if="can('ecommerce.listings.create')"
                variant="primary"
                size="md"
                v-on:click="openCreateRoot"
            >
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('backend.ecommerce.listing_categories.add') }}
            </AppButton>
        </div>

        <AppMessage variant="info">
            {{ t('backend.ecommerce.listing_categories.dnd_hint') }}
        </AppMessage>

        <AppNoData v-if="!tree.length" :message="t('backend.ecommerce.listing_categories.empty')" />

        <VueDraggable
            v-else
            v-model="tree"
            :group="{ name: 'listing-categories', pull: true, put: true }"
            handle=".drag-handle"
            :animation="150"
            ghost-class="opacity-50"
            class="space-y-1"
            v-on:end="onDragEnd"
        >
            <template v-for="node in tree" :key="node.id">
                <ListingCategoryNode
                    :node="node"
                    :active-locale="activeLocale"
                    group-name="listing-categories"
                    :collapsed="collapsed"
                    :can-edit="can('ecommerce.listings.edit')"
                    :can-delete="can('ecommerce.listings.delete')"
                    v-on:toggle-collapse="toggleCollapsed($event)"
                    v-on:edit="openEdit($event)"
                    v-on:delete="confirmDelete($event)"
                    v-on:add-child="openCreateChild($event)"
                    v-on:end="onDragEnd"
                />
            </template>
        </VueDraggable>

        <AppModal
            :show="showCreate || showEdit"
            max-width="lg"
            :title="showEdit ? t('backend.ecommerce.listing_categories.edit', { name: displayName(editingCategory ?? {}) }) : t('backend.ecommerce.listing_categories.create')"
            :icon="FolderTree"
            :closeable="false"
            v-on:close="showCreate = false; showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="showEdit ? submitEdit() : submitCreate()">
                <AppMultiselect
                    v-model="editForm.parentId"
                    :options="parentOptions"
                    :label="t('backend.ecommerce.listing_categories.parent')"
                    :placeholder="t('backend.ecommerce.listing_categories.parent_placeholder')"
                    :allow-empty="true"
                    track-by="id"
                    option-label="label"
                />

                <AppImagePickerField
                    v-model="formImage"
                    :label="t('backend.ecommerce.listing_categories.image')"
                />

                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <span class="text-sm text-secondary">{{ t('backend.ecommerce.listing_categories.visible_label') }}</span>
                    <AppToggle v-model="editForm.isVisible" />
                </div>

                <div class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('backend.ecommerce.listing_categories.translations') }}</label>
                    <div v-if="locales.length > 1" class="flex gap-1">
                        <AppTab
                            v-for="locale in locales"
                            :key="locale.code"
                            size="xs"
                            :active="activeTab === locale.code"
                            active-class="bg-accent-600 text-white"
                            inactive-class="bg-surface-2 text-secondary hover:bg-surface-3"
                            v-on:click="activeTab = locale.code"
                        >
                            {{ locale.label }}
                        </AppTab>
                    </div>
                    <AppInput
                        v-model="editForm.translations[activeTab].name"
                        :label="t('backend.ecommerce.listing_categories.name')"
                        :placeholder="t('backend.ecommerce.listing_categories.name_placeholder')"
                        :error="(showEdit ? editErrors : createErrors)['translations[' + activeTab + '].name']"
                        required
                        v-on:blur="autoSlug(activeTab)"
                    />
                    <AppInput
                        v-model="editForm.translations[activeTab].slug"
                        :label="t('backend.ecommerce.listing_categories.slug')"
                        :placeholder="t('backend.ecommerce.listing_categories.slug_placeholder')"
                        :error="(showEdit ? editErrors : createErrors)['translations[' + activeTab + '].slug']"
                    />
                    <AppTextarea
                        v-model="editForm.translations[activeTab].description"
                        :label="t('backend.ecommerce.listing_categories.description')"
                        :placeholder="t('backend.ecommerce.listing_categories.description_placeholder')"
                        :rows="3"
                    />
                    <AppInput
                        v-model="editForm.translations[activeTab].seoTitle"
                        :label="t('backend.ecommerce.listing_categories.seo_title')"
                        :placeholder="t('backend.ecommerce.listing_categories.seo_title_placeholder')"
                    />
                    <AppTextarea
                        v-model="editForm.translations[activeTab].seoDescription"
                        :label="t('backend.ecommerce.listing_categories.seo_description')"
                        :placeholder="t('backend.ecommerce.listing_categories.seo_description_placeholder')"
                        :rows="2"
                    />
                </div>

                <slot
                    name="extra-form-fields"
                    :editForm="editForm"
                    :errors="showEdit ? editErrors : createErrors"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false; showEdit = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="showEdit ? editLoading : createLoading"
                        v-on:click="showEdit ? submitEdit() : submitCreate()"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">{{ t('backend.ecommerce.listing_categories.delete_confirm', { name: displayName(pendingDelete ?? {}) }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
