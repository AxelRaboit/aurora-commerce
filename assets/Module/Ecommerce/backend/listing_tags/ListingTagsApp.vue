<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useClientFilteredList } from "@/shared/composables/list/useClientFilteredList.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useListingTagsForm } from "@ecommerce/backend/listing_tags/composables/useListingTagsForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppToggle from "@/shared/components/form/AppToggle.vue";
import AppColorPicker from "@/shared/components/form/AppColorPicker.vue";
import AppColorSwatch from "@/shared/components/form/AppColorSwatch.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import { Trash2, Plus, Save, X, Tag, Pencil } from "lucide-vue-next";
import { translatedField } from "@/shared/utils/i18n/pickTranslation.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    tags: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    extraFields: { type: Object, default: () => ({}) },
});

function tagSearchHaystack(tag) {
    const parts = [];
    for (const translation of Object.values(tag.translations ?? {})) {
        if (translation?.name) parts.push(translation.name);
        if (translation?.slug) parts.push(translation.slug);
    }
    return parts.join(" ").toLowerCase();
}

const { searchInput, filteredItems, reload } = useClientFilteredList(
    props.tags,
    props.listPath,
    (tag, query) => tagSearchHaystack(tag).includes(query),
);
const activeTab = ref(props.locales[0]?.code ?? "en");
const activeLocale = computed(() => activeTab.value);

const {
    showCreate,
    showEdit,
    editingTag,
    editForm,
    createErrors,
    createLoading,
    editErrors,
    editLoading,
    openCreate,
    openEdit,
    submitCreate,
    submitEdit,
    autoSlug,
} = useListingTagsForm({
    createPath: props.createPath,
    updatePath: props.updatePath,
    locales: props.locales,
    reset: reload,
    extraFields: props.extraFields,
});

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath,
    reload,
    "backend.ecommerce.listing_tags.deleted",
);

function displayName(tag) {
    if (!tag) return "";
    return translatedField(tag, "name", activeLocale.value, `#${tag.id}`);
}

function displaySlug(tag) {
    if (!tag) return "";
    return translatedField(tag, "slug", activeLocale.value, "");
}
</script>

<template>
    <div class="space-y-4">
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

        <AppListToolbar>
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('backend.ecommerce.listing_tags.searchPlaceholder')"
            />
            <template #actions>
                <AppButton
                    v-if="can('ecommerce.listings.create')"
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="openCreate"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.ecommerce.listing_tags.add') }}
                </AppButton>
            </template>
        </AppListToolbar>

        <div class="sm:hidden space-y-3">
            <div v-for="tag in filteredItems" :key="tag.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-start gap-3">
                    <AppColorSwatch :model-value="tag.color" size="sm" disabled />
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-primary truncate">{{ displayName(tag) }}</p>
                        <p class="text-xs font-mono text-muted mt-0.5 truncate">{{ displaySlug(tag) }}</p>
                    </div>
                    <AppBadge v-if="!tag.isVisible" color="gray">{{ t('backend.ecommerce.listing_tags.hidden') }}</AppBadge>
                </div>
                <div class="flex items-center justify-end gap-0.5 pt-2 border-t border-line">
                    <AppIconButton v-if="can('ecommerce.listings.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(tag)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    <AppIconButton v-if="can('ecommerce.listings.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(tag)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                </div>
            </div>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.listing_tags.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.listing_tags.slug') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.listing_tags.visible_label') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="tag in filteredItems" :key="tag.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <AppColorSwatch :model-value="tag.color" size="sm" disabled />
                                <span class="font-medium text-primary truncate">{{ displayName(tag) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-secondary font-mono text-xs">{{ displaySlug(tag) }}</td>
                        <td class="px-6 py-3">
                            <AppBadge v-if="!tag.isVisible" color="gray">{{ t('backend.ecommerce.listing_tags.hidden') }}</AppBadge>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="can('ecommerce.listings.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(tag)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('ecommerce.listings.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(tag)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!filteredItems.length">
                        <td :colspan="4" class="px-6 py-8 text-center text-sm text-muted">{{ t('backend.ecommerce.listing_tags.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-if="!filteredItems.length" class="sm:hidden py-8 text-center text-sm text-muted">{{ t('backend.ecommerce.listing_tags.empty') }}</p>

        <AppModal
            :show="showCreate || showEdit"
            max-width="lg"
            :title="showEdit ? t('backend.ecommerce.listing_tags.edit', { name: displayName(editingTag ?? {}) }) : t('backend.ecommerce.listing_tags.create')"
            :icon="Tag"
            :closeable="false"
            v-on:close="showCreate = false; showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="showEdit ? submitEdit() : submitCreate()">
                <AppColorPicker
                    v-model="editForm.color"
                    :label="t('backend.ecommerce.listing_tags.color')"
                    :error="(showEdit ? editErrors : createErrors)['color']"
                />
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <span class="text-sm text-secondary">{{ t('backend.ecommerce.listing_tags.visible_label') }}</span>
                    <AppToggle v-model="editForm.isVisible" />
                </div>

                <AppMessage v-if="(showEdit ? editErrors : createErrors)['_global']" variant="error">
                    {{ (showEdit ? editErrors : createErrors)['_global'] }}
                </AppMessage>

                <div class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('backend.ecommerce.listing_tags.translations') }}</label>
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
                        :label="t('backend.ecommerce.listing_tags.name')"
                        :placeholder="t('backend.ecommerce.listing_tags.name_placeholder')"
                        :error="(showEdit ? editErrors : createErrors)['translations[' + activeTab + '].name']"
                        required
                        v-on:blur="autoSlug(activeTab)"
                    />
                    <AppInput
                        v-model="editForm.translations[activeTab].slug"
                        :label="t('backend.ecommerce.listing_tags.slug')"
                        :placeholder="t('backend.ecommerce.listing_tags.slug_placeholder')"
                        :error="(showEdit ? editErrors : createErrors)['translations[' + activeTab + '].slug']"
                    />
                    <AppTextarea
                        v-model="editForm.translations[activeTab].description"
                        :label="t('backend.ecommerce.listing_tags.description')"
                        :placeholder="t('backend.ecommerce.listing_tags.description_placeholder')"
                        :rows="3"
                    />
                </div>

                <slot
                    name="extra-form-fields"
                    :edit-form="editForm"
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
            <p class="text-sm text-primary">{{ t('backend.ecommerce.listing_tags.delete_confirm', { name: displayName(pendingDelete ?? {}) }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
