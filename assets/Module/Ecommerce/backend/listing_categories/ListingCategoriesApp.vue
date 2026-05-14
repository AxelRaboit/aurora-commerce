<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useListingCategoriesForm } from "@ecommerce/backend/listing_categories/composables/useListingCategoriesForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppToggle from "@/shared/components/form/AppToggle.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppImagePickerField from "@/shared/components/form/AppImagePickerField.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import { Pencil, Trash2, Plus, Save, X, FolderTree } from "lucide-vue-next";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    categories: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    extraFields: { type: Object, default: () => ({}) },
});

const items = ref([...props.categories]);
const activeTab = ref(props.locales[0]?.code ?? "en");

async function reload() {
    const res = await fetch(props.listPath, { headers: { Accept: "application/json" } });
    const json = await res.json();
    items.value = json.items ?? [];
}

const parentOptions = computed(() => {
    return items.value.map((c) => {
        const indent = "— ".repeat(c.depth ?? 0);
        const firstTranslation = Object.values(c.translations ?? {})[0];
        return {
            id: c.id,
            label: indent + (firstTranslation?.name ?? `#${c.id}`),
        };
    });
});

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

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath,
    reload,
    "backend.ecommerce.listing_categories.deleted",
);

function displayName(category) {
    const firstLocale = props.locales[0]?.code;
    return category.translations?.[firstLocale]?.name ?? `#${category.id}`;
}

defineSlots();
</script>

<template>
    <div class="space-y-4">
        <div class="flex justify-end">
            <AppButton
                v-if="can('ecommerce.listings.create')"
                variant="primary"
                size="md"
                v-on:click="openCreate"
            >
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('backend.ecommerce.listing_categories.add') }}
            </AppButton>
        </div>

        <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.listing_categories.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.listing_categories.slug') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.listing_categories.position') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.listing_categories.visibility') }}</th>
                        <slot name="extra-headers" />
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="category in items" :key="category.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded bg-surface-2 overflow-hidden shrink-0 flex items-center justify-center">
                                    <AppImage v-if="category.image" :src="category.image.url" :alt="category.image.alt ?? ''" object-fit="cover" />
                                    <span v-else class="text-muted text-xs">—</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-primary truncate" :style="{ paddingLeft: (category.depth * 12) + 'px' }">
                                        {{ displayName(category) }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3 font-mono text-xs text-secondary">{{ Object.values(category.translations ?? {})[0]?.slug ?? '' }}</td>
                        <td class="px-6 py-3 text-secondary">{{ category.position }}</td>
                        <td class="px-6 py-3">
                            <AppBadge :color="category.isVisible ? 'emerald' : 'slate'">
                                {{ t(category.isVisible ? 'backend.ecommerce.listing_categories.visible' : 'backend.ecommerce.listing_categories.hidden') }}
                            </AppBadge>
                        </td>
                        <slot name="extra-cells" :category="category" />
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="can('ecommerce.listings.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(category)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('ecommerce.listings.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(category)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items?.length">
                        <td :colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t('backend.ecommerce.listing_categories.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppModal
            :show="showCreate || showEdit"
            :title="showEdit ? t('backend.ecommerce.listing_categories.edit', { name: displayName(editingCategory ?? {}) }) : t('backend.ecommerce.listing_categories.create')"
            :icon="FolderTree"
            :closeable="false"
            v-on:close="showCreate = false; showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="showEdit ? submitEdit() : submitCreate()">
                <AppSelect
                    v-model="editForm.parentId"
                    :label="t('backend.ecommerce.listing_categories.parent')"
                >
                    <option value="">{{ t('backend.ecommerce.listing_categories.no_parent') }}</option>
                    <option
                        v-for="opt in parentOptions"
                        v-show="!editingCategory || opt.id !== editingCategory.id"
                        :key="opt.id"
                        :value="opt.id"
                    >
                        {{ opt.label }}
                    </option>
                </AppSelect>

                <AppInput
                    v-model.number="editForm.position"
                    type="number"
                    :label="t('backend.ecommerce.listing_categories.position')"
                />

                <AppImagePickerField
                    v-model="formImage"
                    :label="t('backend.ecommerce.listing_categories.image')"
                />

                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <span class="text-sm text-secondary">{{ t('backend.ecommerce.listing_categories.visible_label') }}</span>
                    <AppToggle v-model="editForm.isVisible" />
                </div>

                <div class="border border-line rounded-lg">
                    <div class="flex border-b border-line">
                        <button
                            v-for="locale in locales"
                            :key="locale.code"
                            type="button"
                            class="px-4 py-2 text-sm font-medium"
                            :class="activeTab === locale.code ? 'text-primary border-b-2 border-accent' : 'text-muted'"
                            v-on:click="activeTab = locale.code"
                        >
                            {{ locale.label }}
                        </button>
                    </div>
                    <div v-for="locale in locales" v-show="activeTab === locale.code" :key="locale.code" class="p-4 space-y-3">
                        <AppInput
                            v-model="editForm.translations[locale.code].name"
                            :label="t('backend.ecommerce.listing_categories.name')"
                            :error="(showEdit ? editErrors : createErrors)['translations[' + locale.code + '].name']"
                            required
                        />
                        <AppInput
                            v-model="editForm.translations[locale.code].slug"
                            :label="t('backend.ecommerce.listing_categories.slug')"
                            :placeholder="t('backend.ecommerce.listing_categories.slug_placeholder')"
                        />
                        <AppTextarea
                            v-model="editForm.translations[locale.code].description"
                            :label="t('backend.ecommerce.listing_categories.description')"
                            :rows="3"
                        />
                        <AppInput
                            v-model="editForm.translations[locale.code].seoTitle"
                            :label="t('backend.ecommerce.listing_categories.seo_title')"
                        />
                        <AppTextarea
                            v-model="editForm.translations[locale.code].seoDescription"
                            :label="t('backend.ecommerce.listing_categories.seo_description')"
                            :rows="2"
                        />
                    </div>
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
