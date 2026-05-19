<script setup>
import { useI18n } from "vue-i18n";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useClientFilteredList } from "@/shared/composables/list/useClientFilteredList.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useContactTagsForm } from "@crm/backend/contact-tags/composables/useContactTagsForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppColorPicker from "@/shared/components/form/picker/AppColorPicker.vue";
import AppColorSwatch from "@/shared/components/form/picker/AppColorSwatch.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import { Trash2, Plus, Save, X, Tag, Pencil } from "lucide-vue-next";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    tags: { type: Array, default: () => [] },
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    extraFields: { type: Object, default: () => ({}) },
});

const { searchInput, filteredItems, reload } = useClientFilteredList(
    props.tags,
    props.listPath,
    (tag, query) =>
        (tag.label ?? "").toLowerCase().includes(query)
        || (tag.slug ?? "").toLowerCase().includes(query),
);

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
} = useContactTagsForm({
    createPath: props.createPath,
    updatePath: props.updatePath,
    reset: reload,
    extraFields: props.extraFields,
});

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath,
    reload,
    "backend.crm.contact_tags.deleted",
);

function displayLabel(tag) {
    if (!tag) return "";
    return tag.label || `#${tag.id ?? ""}`;
}
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('backend.crm.contact_tags.searchPlaceholder')"
            />
            <template #actions>
                <AppButton
                    v-if="can('crm.contacts.create')"
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="openCreate"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.crm.contact_tags.add') }}
                </AppButton>
            </template>
        </AppListToolbar>

        <div class="sm:hidden space-y-3">
            <div v-for="tag in filteredItems" :key="tag.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-center gap-3">
                    <AppColorSwatch :model-value="tag.color" size="sm" disabled />
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-primary truncate">{{ displayLabel(tag) }}</p>
                        <p class="text-xs font-mono text-muted mt-0.5 truncate">{{ tag.slug }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-0.5 pt-2 border-t border-line">
                    <AppIconButton v-if="can('crm.contacts.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(tag)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    <AppIconButton v-if="can('crm.contacts.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(tag)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                </div>
            </div>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.crm.contact_tags.label') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.crm.contact_tags.slug') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="tag in filteredItems" :key="tag.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <AppColorSwatch :model-value="tag.color" size="sm" disabled />
                                <span class="font-medium text-primary truncate">{{ displayLabel(tag) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-secondary font-mono text-xs">{{ tag.slug }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="can('crm.contacts.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(tag)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('crm.contacts.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(tag)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!filteredItems.length">
                        <td :colspan="3" class="px-6 py-8 text-center text-sm text-muted">{{ t('backend.crm.contact_tags.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-if="!filteredItems.length" class="sm:hidden py-8 text-center text-sm text-muted">{{ t('backend.crm.contact_tags.empty') }}</p>

        <AppModal
            :show="showCreate || showEdit"
            max-width="lg"
            :title="showEdit ? t('backend.crm.contact_tags.edit', { name: displayLabel(editingTag ?? {}) }) : t('backend.crm.contact_tags.create')"
            :icon="Tag"
            :closeable="false"
            v-on:close="showCreate = false; showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="showEdit ? submitEdit() : submitCreate()">
                <AppInput
                    v-model="editForm.label"
                    :label="t('backend.crm.contact_tags.label')"
                    :placeholder="t('backend.crm.contact_tags.label_placeholder')"
                    :error="(showEdit ? editErrors : createErrors)['label']"
                    required
                    v-on:blur="autoSlug"
                />
                <AppInput
                    v-model="editForm.slug"
                    :label="t('backend.crm.contact_tags.slug')"
                    :placeholder="t('backend.crm.contact_tags.slug_placeholder')"
                    :error="(showEdit ? editErrors : createErrors)['slug']"
                />
                <AppColorPicker
                    v-model="editForm.color"
                    :label="t('backend.crm.contact_tags.color')"
                    :error="(showEdit ? editErrors : createErrors)['color']"
                />

                <AppMessage v-if="(showEdit ? editErrors : createErrors)['_global']" variant="error">
                    {{ (showEdit ? editErrors : createErrors)['_global'] }}
                </AppMessage>

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
            <p class="text-sm text-primary">{{ t('backend.crm.contact_tags.delete_confirm', { name: displayLabel(pendingDelete ?? {}) }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
