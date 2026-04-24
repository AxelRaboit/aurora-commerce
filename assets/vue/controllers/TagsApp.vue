<script setup>
import { useI18n } from "vue-i18n";
import AppInput from "@/components/AppInput.vue";
import AppModal from "@/components/AppModal.vue";
import AppNoData from "@/components/AppNoData.vue";
import { useDateFormat } from "@/composables/useDateFormat.js";
import { useTagList } from "@/admin/tags/composables/useTagList.js";
import { useTagCreate } from "@/admin/tags/composables/useTagCreate.js";
import { useTagEdit } from "@/admin/tags/composables/useTagEdit.js";
import { useTagDelete } from "@/admin/tags/composables/useTagDelete.js";
import { Pencil, Trash2, Plus, Tag, Search } from "lucide-vue-next";
import AppButton from "@/components/AppButton.vue";
import AppIconButton from "@/components/AppIconButton.vue";

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const props = defineProps({
    tagsPath: { type: String, required: true },
    tags: { type: Object, default: () => ({ items: [], total: 0, page: 1, totalPages: 1 }) },
    search: { type: String, default: "" },
    createPath: { type: String, required: true },
    editPath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const { tags, page, totalPages, search: searchInput, addTag, updateTag, removeTag, performSearch, goToPage } = useTagList(props.tagsPath, props.tags, props.search);
const create = useTagCreate(props.createPath, (tag) => addTag(tag));
const edit = useTagEdit(props.editPath, (tag) => updateTag(tag));
const deleteTag = useTagDelete(props.deletePath, (id) => removeTag(id), "admin.tags.deleted");
</script>

<template>
    <div class="space-y-4">
        <!-- Toolbar -->
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" :stroke-width="2" />
                <input
                    v-model="searchInput"
                    type="text"
                    :placeholder="t('admin.tags.searchPlaceholder')"
                    class="w-full pl-9 pr-4 py-2 rounded-lg bg-surface-2 border border-line/60 text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                    v-on:keyup.enter="performSearch"
                >
            </div>
            <AppButton variant="secondary" size="md" class="w-full sm:w-auto" v-on:click="performSearch">
                <Search class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.users.search') }}
            </AppButton>
            <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="create.open()">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.tags.add') }}
            </AppButton>
        </div>

        <!-- Mobile cards -->
        <div class="sm:hidden space-y-3">
            <AppNoData v-if="!tags.length" :message="t('admin.tags.empty')" />
            <div v-for="tag in tags" :key="tag.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-center gap-2">
                    <Tag class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" />
                    <p class="font-medium text-primary">{{ tag.name }}</p>
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-line">
                    <p class="text-xs text-muted font-mono">{{ tag.slug }}</p>
                    <div class="flex items-center gap-1">
                        <AppIconButton color="indigo" v-on:click="edit.open(tag)">
                            <Pencil class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton color="rose" v-on:click="deleteTag.confirm(tag)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop table -->
        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 border-b border-line">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.tags.name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.tags.slug') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('admin.tags.createdAt') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.tags.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-if="!tags.length">
                        <td colspan="4"><AppNoData :message="t('admin.tags.empty')" /></td>
                    </tr>
                    <tr v-for="tag in tags" :key="tag.id" class="hover:bg-surface-2/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <Tag class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" />
                                <span class="font-medium text-primary">{{ tag.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-secondary font-mono text-xs hidden md:table-cell">{{ tag.slug }}</td>
                        <td class="px-4 py-3 text-secondary hidden lg:table-cell">{{ formatDateShort(tag.createdAt) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <AppIconButton color="indigo" v-on:click="edit.open(tag)">
                                    <Pencil class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton color="rose" v-on:click="deleteTag.confirm(tag)">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between text-sm text-secondary">
            <span>{{ t('common.pagination', { page, totalPages }) }}</span>
            <div class="flex gap-1">
                <button
                    v-for="p in totalPages"
                    :key="p"
                    type="button"
                    class="w-8 h-8 rounded-md text-sm font-medium transition-colors"
                    :class="p === page ? 'bg-indigo-600 text-white' : 'text-secondary hover:bg-surface-2'"
                    v-on:click="goToPage(p)"
                >
                    {{ p }}
                </button>
            </div>
        </div>

        <!-- Create modal -->
        <AppModal :show="create.showModal.value" v-on:close="create.showModal.value = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.tags.add') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="create.submit()">
                <AppInput
                    v-model="create.name.value"
                    :label="t('admin.tags.name')"
                    :error="create.errors.value.name"
                    required
                />
                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="create.showModal.value = false">{{ t('common.cancel') }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="create.loading.value">{{ t('common.create') }}</AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Edit modal -->
        <AppModal :show="!!edit.editingTag.value" v-on:close="edit.editingTag.value = null">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.tags.edit') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="edit.submit()">
                <AppInput
                    v-model="edit.name.value"
                    :label="t('admin.tags.name')"
                    :error="edit.errors.value.name"
                    required
                />
                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="edit.editingTag.value = null">{{ t('common.cancel') }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="edit.loading.value">{{ t('common.save') }}</AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Delete modal -->
        <AppModal :show="!!deleteTag.pendingDelete.value" max-width="sm" v-on:close="deleteTag.pendingDelete.value = null">
            <p class="text-sm text-primary">{{ t('admin.tags.deleteConfirm', { name: deleteTag.pendingDelete.value?.name }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deleteTag.pendingDelete.value = null">{{ t('common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteTag.loading.value" v-on:click="deleteTag.submit()">{{ t('common.delete') }}</AppButton>
            </div>
        </AppModal>
    </div>
</template>
