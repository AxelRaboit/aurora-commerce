<script setup>
import { useI18n } from "vue-i18n";
import AppInput from "@/components/AppInput.vue";
import AppModal from "@/components/AppModal.vue";
import AppNoData from "@/components/AppNoData.vue";
import { useDateFormat } from "@/composables/useDateFormat.js";
import { useTagList } from "./composables/useTagList.js";
import { useTagCreate } from "./composables/useTagCreate.js";
import { useTagEdit } from "./composables/useTagEdit.js";
import { useTagDelete } from "./composables/useTagDelete.js";
import { Pencil, Trash2, Plus, Tag, Search } from "lucide-vue-next";

const { t: translate } = useI18n();
const { formatDateShort } = useDateFormat();

const props = defineProps({
    tagsPath: { type: String, required: true },
    tags: { type: String, default: '{"items":[],"total":0,"page":1,"totalPages":1}' },
    search: { type: String, default: "" },
    createPath: { type: String, required: true },
    editPath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const { tags, page, totalPages, search: searchInput, addTag, updateTag, removeTag, performSearch, goToPage } = useTagList(props.tagsPath, props.tags, props.search);
const create = useTagCreate(props.createPath, (tag) => addTag(tag));
const edit = useTagEdit(props.editPath, (tag) => updateTag(tag));
const deleteTag = useTagDelete(props.deletePath, (id) => removeTag(id));
</script>

<template>
    <div class="space-y-4">
        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" :stroke-width="2" />
                <input
                    v-model="searchInput"
                    type="text"
                    :placeholder="translate('admin.tags.searchPlaceholder')"
                    class="w-full pl-9 pr-4 py-2 rounded-lg bg-surface-2 border border-line text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    v-on:keyup.enter="performSearch"
                >
            </div>
            <button
                type="button"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-surface-2 border border-line hover:bg-surface-3 text-secondary transition-colors"
                v-on:click="performSearch"
            >
                <Search class="w-4 h-4" :stroke-width="2" />
                {{ translate('admin.users.search') }}
            </button>
            <button
                type="button"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
                v-on:click="create.open()"
            >
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ translate('admin.tags.add') }}
            </button>
        </div>

        <!-- Mobile cards -->
        <div class="sm:hidden space-y-3">
            <AppNoData v-if="!tags.length" :message="translate('admin.tags.empty')" />
            <div v-for="tag in tags" :key="tag.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-center gap-2">
                    <Tag class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" />
                    <p class="font-medium text-primary">{{ tag.name }}</p>
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-line">
                    <p class="text-xs text-muted font-mono">{{ tag.slug }}</p>
                    <div class="flex items-center gap-1">
                        <button type="button" class="p-1.5 text-muted hover:text-indigo-400 transition-colors rounded" v-on:click="edit.open(tag)">
                            <Pencil class="w-4 h-4" :stroke-width="2" />
                        </button>
                        <button type="button" class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" v-on:click="deleteTag.confirm(tag)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop table -->
        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 border-b border-line">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary">{{ translate('admin.tags.name') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">{{ translate('admin.tags.slug') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ translate('admin.tags.createdAt') }}</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-primary">{{ translate('admin.tags.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-if="!tags.length">
                        <td colspan="4"><AppNoData :message="translate('admin.tags.empty')" /></td>
                    </tr>
                    <tr v-for="tag in tags" :key="tag.id" class="hover:bg-surface-2/50 transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <Tag class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" />
                                <span class="font-medium text-primary">{{ tag.name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-secondary font-mono text-xs hidden md:table-cell">{{ tag.slug }}</td>
                        <td class="px-6 py-3 text-secondary hidden lg:table-cell">{{ formatDateShort(tag.createdAt) }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" class="p-1.5 text-muted hover:text-indigo-400 transition-colors rounded" v-on:click="edit.open(tag)">
                                    <Pencil class="w-4 h-4" :stroke-width="2" />
                                </button>
                                <button type="button" class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" v-on:click="deleteTag.confirm(tag)">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between text-sm text-secondary">
            <span>{{ translate('common.pagination', { page, totalPages }) }}</span>
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
            <h3 class="text-lg font-semibold text-primary">{{ translate('admin.tags.add') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="create.submit()">
                <AppInput
                    v-model="create.name.value"
                    :label="translate('admin.tags.name')"
                    :error="create.errors.value.name"
                    required
                />
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" class="px-3 py-2 text-sm font-medium rounded-lg text-secondary hover:text-primary hover:bg-surface-2" v-on:click="create.showModal.value = false">{{ translate('common.cancel') }}</button>
                    <button type="submit" :disabled="create.loading.value" class="px-3 py-2 text-sm font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50">{{ translate('common.create') }}</button>
                </div>
            </form>
        </AppModal>

        <!-- Edit modal -->
        <AppModal :show="!!edit.editingTag.value" v-on:close="edit.editingTag.value = null">
            <h3 class="text-lg font-semibold text-primary">{{ translate('admin.tags.edit') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="edit.submit()">
                <AppInput
                    v-model="edit.name.value"
                    :label="translate('admin.tags.name')"
                    :error="edit.errors.value.name"
                    required
                />
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" class="px-3 py-2 text-sm font-medium rounded-lg text-secondary hover:text-primary hover:bg-surface-2" v-on:click="edit.editingTag.value = null">{{ translate('common.cancel') }}</button>
                    <button type="submit" :disabled="edit.loading.value" class="px-3 py-2 text-sm font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50">{{ translate('common.save') }}</button>
                </div>
            </form>
        </AppModal>

        <!-- Delete modal -->
        <AppModal :show="!!deleteTag.pendingDelete.value" max-width="sm" v-on:close="deleteTag.pendingDelete.value = null">
            <p class="text-sm text-primary">{{ translate('admin.tags.deleteConfirm', { name: deleteTag.pendingDelete.value?.name }) }}</p>
            <div class="flex justify-end gap-2">
                <button type="button" class="px-3 py-1.5 text-sm text-secondary hover:text-primary transition-colors" v-on:click="deleteTag.pendingDelete.value = null">{{ translate('common.cancel') }}</button>
                <button type="button" :disabled="deleteTag.loading.value" class="px-3 py-1.5 text-sm bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition-colors disabled:opacity-50" v-on:click="deleteTag.submit()">{{ translate('common.delete') }}</button>
            </div>
        </AppModal>
    </div>
</template>
