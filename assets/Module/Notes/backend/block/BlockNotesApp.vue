<script setup>
import { useI18n } from 'vue-i18n';
import { useBlockNotesPage } from '@notes/backend/block/composables/useBlockNotesPage.js';
import BlockTreeItem from '@notes/backend/block/components/BlockTreeItem.vue';
import BlockTagManagerModal from '@notes/backend/block/components/BlockTagManagerModal.vue';
import AppBlockEditor from '@shared/components/editor/AppBlockEditor.vue';
import AppButton from '@shared/components/action/AppButton.vue';
import AppIconButton from '@shared/components/action/AppIconButton.vue';
import AppInput from '@shared/components/form/input/AppInput.vue';
import AppSearchInput from '@shared/components/form/input/AppSearchInput.vue';
import AppTagsInput from '@shared/components/form/select/AppTagsInput.vue';
import AppNoData from '@shared/components/feedback/AppNoData.vue';
import AppModal from '@shared/components/overlay/AppModal.vue';
import AppModalFooter from '@shared/components/overlay/AppModalFooter.vue';
import AppTab from '@shared/components/nav/AppTab.vue';
import { Plus, Trash2, FileText, X, Settings2, Menu } from 'lucide-vue-next';

const props = defineProps({
    notes: { type: Array, default: () => [] },
    listPath: { type: String, required: true },
    showPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    movePath: { type: String, required: true },
    reorderPath: { type: String, required: true },
    searchPath: { type: String, required: true },
    tagsListPath: { type: String, required: true },
    tagsRenamePath: { type: String, required: true },
    tagsMergePath: { type: String, required: true },
    tagsDeletePath: { type: String, required: true },
    imageUploadPath: { type: String, required: true },
    imageMaxEdge: { type: Number, default: 2048 },
    imageQuality: { type: Number, default: 0.85 },
    /**
     * Client-extension hook — see `docs/aurora-core/dev/entity_extensibility_convention.md`.
     * Same shape as MarkdownNotesApp's `extraFields`.
     */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();

const {
    isMobile,
    sidebarOpen,
    tagsApi,
    selectedId,
    selectedNote,
    form,
    deleting,
    lastSavedAt,
    pendingDelete,
    selectNote,
    createNote,
    requestDelete,
    cancelDelete,
    confirmDelete,
    setBlocks,
    tree,
    treeQuery,
    availableTags,
    selectedTags,
    toggleTag,
    clearTags,
    onTagsChanged,
    tagManagerOpen,
    dragEnabled,
    draggingId,
    dragOverId,
    rootDragOver,
    onDragStart,
    onDragEnd,
    onDragOverNote,
    onDragLeaveNote,
    onDragOverRoot,
    onDragLeaveRoot,
    onDropOnNote,
    onDropOnRoot,
    lastSavedRelative,
    saveStatusDisplay,
} = useBlockNotesPage(props, t);
</script>

<template>
    <div class="relative flex h-[calc(100vh-8rem)] bg-surface rounded-xl border border-line overflow-hidden">
        <div
            v-if="isMobile && sidebarOpen"
            class="absolute inset-0 z-30 bg-black/40 md:hidden"
            v-on:click="sidebarOpen = false"
        />

        <aside
            class="w-72 shrink-0 border-r border-line flex flex-col bg-surface md:bg-surface-2/30 z-40 transition-transform duration-200 md:relative md:translate-x-0 md:shadow-none absolute inset-y-0 left-0 shadow-xl"
            :class="!isMobile || sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="p-3 border-b border-line flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-primary">{{ t('notes.block.title') }}</h2>
                <div class="flex items-center gap-1">
                    <AppIconButton
                        :title="t('notes.block.create_root')"
                        size="sm"
                        variant="ghost"
                        v-on:click="() => createNote(null)"
                    >
                        <Plus class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton
                        class="md:hidden"
                        :title="t('shared.common.close')"
                        size="sm"
                        variant="ghost"
                        v-on:click="sidebarOpen = false"
                    >
                        <X class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                </div>
            </div>

            <div class="px-3 pt-2">
                <AppSearchInput
                    v-model="treeQuery"
                    :placeholder="t('notes.block.search_placeholder')"
                />
            </div>

            <slot name="extra-headers" />

            <div v-if="availableTags.length > 0" class="px-3 pt-2">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-muted uppercase tracking-wide">
                        {{ t('notes.block.tags.filter_label') }}
                    </span>
                    <div class="flex items-center gap-2">
                        <AppButton
                            v-if="selectedTags.length > 0"
                            variant="link"
                            size="none"
                            v-on:click="clearTags"
                        >
                            {{ t('notes.block.tags.clear') }}
                        </AppButton>
                        <AppIconButton
                            size="sm"
                            variant="ghost"
                            :title="t('notes.block.tags.manage.title')"
                            v-on:click="tagManagerOpen = true"
                        >
                            <Settings2 class="w-3.5 h-3.5" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1">
                    <AppTab
                        v-for="tag in availableTags"
                        :key="tag"
                        size="xs"
                        :active="selectedTags.includes(tag)"
                        v-on:click="toggleTag(tag)"
                    >
                        {{ tag }}
                    </AppTab>
                </div>
            </div>

            <div
                class="flex-1 overflow-auto p-2 transition-colors"
                :class="rootDragOver ? 'bg-accent-50 dark:bg-accent-900/10 ring-1 ring-accent-500/40 rounded-md' : ''"
                v-on:dragover="dragEnabled ? onDragOverRoot($event) : null"
                v-on:dragleave="dragEnabled ? onDragLeaveRoot($event) : null"
                v-on:drop="dragEnabled ? onDropOnRoot($event) : null"
            >
                <div v-if="tree.length > 0" class="space-y-0.5">
                    <BlockTreeItem
                        v-for="node in tree"
                        :key="node.id"
                        :node="node"
                        :selected-id="selectedId"
                        :draggable="dragEnabled"
                        :dragging-id="draggingId"
                        :drag-over-id="dragOverId"
                        v-on:select="selectNote"
                        v-on:create-child="createNote"
                        v-on:delete="requestDelete"
                        v-on:drag-start="onDragStart"
                        v-on:drag-end="onDragEnd"
                        v-on:drag-over="onDragOverNote"
                        v-on:drag-leave="onDragLeaveNote"
                        v-on:drop="onDropOnNote"
                    >
                        <template #extra-cells="{ note }">
                            <slot name="extra-cells" :note="note" />
                        </template>
                    </BlockTreeItem>
                </div>
                <AppNoData
                    v-else-if="treeQuery.trim() !== ''"
                    :title="t('notes.block.search_no_results')"
                    :description="t('notes.block.search_no_results_description', { query: treeQuery })"
                    :icon="FileText"
                />
                <AppNoData
                    v-else-if="selectedTags.length > 0"
                    :title="t('notes.block.tags.no_results')"
                    :description="t('notes.block.tags.no_results_description')"
                    :icon="FileText"
                />
                <AppNoData
                    v-else
                    :title="t('notes.block.empty.title')"
                    :description="t('notes.block.empty.description')"
                    :icon="FileText"
                />
            </div>
        </aside>

        <section class="flex-1 flex flex-col min-w-0">
            <div v-if="selectedNote" class="flex-1 flex flex-col overflow-hidden">
                <header class="p-4 border-b border-line flex flex-col gap-2">
                    <div class="flex flex-wrap items-center gap-2 md:gap-3">
                        <AppIconButton
                            class="md:hidden"
                            :title="t('notes.block.title')"
                            size="md"
                            variant="ghost"
                            v-on:click="sidebarOpen = true"
                        >
                            <Menu class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>

                        <AppInput
                            v-model="form.title"
                            :placeholder="t('notes.block.title_placeholder')"
                            class="flex-1 min-w-0 text-lg font-medium"
                        />

                        <div class="flex items-center gap-3 shrink-0">
                            <span
                                v-if="saveStatusDisplay"
                                class="inline-flex items-center gap-1.5 text-xs"
                                :class="saveStatusDisplay.classes"
                            >
                                <component
                                    :is="saveStatusDisplay.icon"
                                    class="w-3.5 h-3.5"
                                    :class="saveStatusDisplay.spin ? 'animate-spin' : ''"
                                    :stroke-width="2"
                                />
                                {{ saveStatusDisplay.label }}
                            </span>
                            <span
                                v-if="lastSavedAt"
                                class="text-xs text-muted"
                                :title="lastSavedAt.toLocaleString()"
                            >
                                {{ t('shared.common.autosave.last_saved', { time: lastSavedRelative }) }}
                            </span>
                        </div>
                    </div>

                    <AppTagsInput
                        v-model="form.tags"
                        :placeholder="t('notes.block.tags.add_placeholder')"
                    />

                    <slot name="extra-form-fields" :form="form" />
                </header>

                <div class="flex-1 p-4 overflow-auto">
                    <AppBlockEditor
                        :key="selectedId"
                        :model-value="form.blocks"
                        :upload-url="imageUploadPath"
                        :placeholder="t('notes.block.editor.placeholder')"
                        v-on:update:model-value="setBlocks"
                    />
                </div>
            </div>

            <div v-else class="flex-1 flex flex-col">
                <header class="p-3 border-b border-line flex items-center gap-2 md:hidden">
                    <AppIconButton
                        :title="t('notes.block.title')"
                        size="md"
                        variant="ghost"
                        v-on:click="sidebarOpen = true"
                    >
                        <Menu class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <h2 class="text-sm font-semibold text-primary">{{ t('notes.block.title') }}</h2>
                </header>
                <div class="flex-1 flex items-center justify-center text-muted text-sm">
                    <AppNoData
                        :title="t('notes.block.no_selection.title')"
                        :description="t('notes.block.no_selection.description')"
                        :icon="FileText"
                    />
                </div>
            </div>
        </section>

        <BlockTagManagerModal
            :show="tagManagerOpen"
            :api="tagsApi"
            v-on:close="tagManagerOpen = false"
            v-on:changed="onTagsChanged"
        />

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="!deleting"
            :title="t('notes.block.delete')"
            :icon="Trash2"
            v-on:close="cancelDelete"
        >
            <p class="text-sm text-primary">
                {{ t('notes.block.confirm_delete', { title: pendingDelete?.title || t('notes.block.untitled') }) }}
            </p>
            <p class="text-sm text-secondary mt-2">
                {{ t('notes.block.delete_warning') }}
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" :disabled="deleting" v-on:click="cancelDelete">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('notes.block.cancel') }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="deleting" v-on:click="confirmDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('notes.block.delete') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
