<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMarkdownNotesApi } from '@notes/backend/markdown/composables/useMarkdownNotesApi.js';
import { useNotesEditor } from '@notes/backend/markdown/composables/useNotesEditor.js';
import { useNoteTree } from '@notes/backend/markdown/composables/useNoteTree.js';
import { useNoteDragDrop } from '@notes/backend/markdown/composables/useNoteDragDrop.js';
import { useViewMode } from '@notes/backend/markdown/composables/useViewMode.js';
import { useResizable } from '@shared/composables/useResizable.js';
import NoteTreeItem from '@notes/backend/markdown/components/NoteTreeItem.vue';
import NotePreview from '@notes/backend/markdown/components/NotePreview.vue';
import NoteSidePanel from '@notes/backend/markdown/components/NoteSidePanel.vue';
import AppButton from '@shared/components/action/AppButton.vue';
import AppIconButton from '@shared/components/action/AppIconButton.vue';
import AppInput from '@shared/components/form/AppInput.vue';
import AppSearchInput from '@shared/components/form/AppSearchInput.vue';
import AppTextarea from '@shared/components/form/AppTextarea.vue';
import AppNoData from '@shared/components/feedback/AppNoData.vue';
import AppModal from '@shared/components/overlay/AppModal.vue';
import AppModalFooter from '@shared/components/overlay/AppModalFooter.vue';
import AppTab from '@shared/components/nav/AppTab.vue';
import { Plus, Save, Trash2, FileText, Pencil, Eye, Columns, PanelRightOpen, PanelRightClose, X } from 'lucide-vue-next';

const props = defineProps({
    notes: { type: Array, default: () => [] },
    listPath: { type: String, required: true },
    showPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    movePath: { type: String, required: true },
    reorderPath: { type: String, required: true },
    backlinksPath: { type: String, required: true },
    unlinkedMentionsPath: { type: String, required: true },
    graphPath: { type: String, required: true },
});

const { t } = useI18n();
const api = useMarkdownNotesApi(props);

// Domain state + actions
const {
    notes,
    selectedId,
    selectedNote,
    form,
    isDirty,
    saving,
    deleting,
    selectNote,
    createNote,
    saveSelected,
    pendingDelete,
    requestDelete,
    cancelDelete,
    confirmDelete,
    onWikiLinkClick,
    onCheckboxToggle,
    refreshList,
} = useNotesEditor({ api, initialNotes: props.notes });

// UI-only state
const sidePanelOpen = ref(false);
const treeQuery = ref('');

const { tree } = useNoteTree(notes, treeQuery);

// Drag-drop hierarchy editing (drop ON a note → child of that note,
// drop on the sidebar root → becomes root). Disabled while the filter
// is active, since the visible tree is a subset of the real one.
const dragEnabled = computed(() => treeQuery.value.trim() === '');
const {
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
} = useNoteDragDrop({ api, refreshList });
const { mode: viewMode } = useViewMode();

const viewModeOptions = [
    { value: 'edit', icon: Pencil, label: t('notes.markdown.view.edit') },
    { value: 'split', icon: Columns, label: t('notes.markdown.view.split') },
    { value: 'preview', icon: Eye, label: t('notes.markdown.view.preview') },
];

// Editor pane width in split mode — drag the seam between editor and preview.
const editorPaneRef = ref(null);
const { size: editorWidth, startResize: startSplitResize, dragging: splitDragging } = useResizable({
    key: 'aurora.notes.markdown.editorWidth',
    defaultValue: 540,
    min: 240,
    max: 1200,
    axis: 'x',
    getOrigin: () => editorPaneRef.value,
});
</script>

<template>
    <div class="flex h-[calc(100vh-8rem)] bg-surface rounded-xl border border-line overflow-hidden">
        <!-- Sidebar tree -->
        <aside class="w-72 shrink-0 border-r border-line flex flex-col bg-surface-2/30">
            <div class="p-3 border-b border-line flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-primary">{{ t('notes.markdown.title') }}</h2>
                <AppIconButton
                    :title="t('notes.markdown.create_root')"
                    size="sm"
                    variant="ghost"
                    v-on:click="() => createNote(null)"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
            </div>

            <div class="px-3 pt-2">
                <AppSearchInput
                    v-model="treeQuery"
                    :placeholder="t('notes.markdown.search_placeholder')"
                />
            </div>

            <div
                class="flex-1 overflow-auto p-2 transition-colors"
                :class="rootDragOver ? 'bg-accent-50 dark:bg-accent-900/10 ring-1 ring-accent-500/40 rounded-md' : ''"
                v-on:dragover="dragEnabled ? onDragOverRoot($event) : null"
                v-on:dragleave="dragEnabled ? onDragLeaveRoot($event) : null"
                v-on:drop="dragEnabled ? onDropOnRoot($event) : null"
            >
                <div v-if="tree.length > 0" class="space-y-0.5">
                    <NoteTreeItem
                        v-for="node in tree"
                        :key="node.id"
                        :node="node"
                        :selected-id="selectedId"
                        :draggable="dragEnabled"
                        :dragging-id="draggingId"
                        :drag-over-id="dragOverId"
                        v-on:select="selectNote"
                        v-on:create-child="createNote"
                        v-on:drag-start="onDragStart"
                        v-on:drag-end="onDragEnd"
                        v-on:drag-over="onDragOverNote"
                        v-on:drag-leave="onDragLeaveNote"
                        v-on:drop="onDropOnNote"
                    />
                </div>
                <AppNoData
                    v-else-if="treeQuery.trim() !== ''"
                    :title="t('notes.markdown.search_no_results')"
                    :description="t('notes.markdown.search_no_results_description', { query: treeQuery })"
                    :icon="FileText"
                />
                <AppNoData
                    v-else
                    :title="t('notes.markdown.empty.title')"
                    :description="t('notes.markdown.empty.description')"
                    :icon="FileText"
                />
            </div>
        </aside>

        <!-- Editor pane -->
        <section class="flex-1 flex flex-col min-w-0">
            <div v-if="selectedNote" class="flex-1 flex flex-col">
                <header class="p-4 border-b border-line flex items-center gap-3">
                    <AppInput
                        v-model="form.title"
                        :placeholder="t('notes.markdown.title_placeholder')"
                        class="flex-1 text-lg font-medium"
                    />

                    <AppIconButton
                        :title="sidePanelOpen ? t('notes.markdown.links.close') : t('notes.markdown.links.open')"
                        size="md"
                        :variant="sidePanelOpen ? 'primary' : 'ghost'"
                        v-on:click="sidePanelOpen = !sidePanelOpen"
                    >
                        <PanelRightClose v-if="sidePanelOpen" class="w-4 h-4" :stroke-width="2" />
                        <PanelRightOpen v-else class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>

                    <!-- View mode toggle (edit / split / preview) — segmented AppTab control -->
                    <div class="inline-flex rounded-md border border-line overflow-hidden">
                        <AppTab
                            v-for="opt in viewModeOptions"
                            :key="opt.value"
                            size="sm"
                            align="center"
                            shape-class="rounded-none"
                            :active="viewMode === opt.value"
                            :title="opt.label"
                            v-on:click="viewMode = opt.value"
                        >
                            <component :is="opt.icon" class="w-4 h-4" :stroke-width="2" />
                        </AppTab>
                    </div>

                    <AppButton
                        variant="primary"
                        size="md"
                        :disabled="!isDirty || saving"
                        :loading="saving"
                        v-on:click="saveSelected"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('notes.markdown.save') }}
                    </AppButton>
                    <AppButton variant="danger" size="md" v-on:click="requestDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('notes.markdown.delete') }}
                    </AppButton>
                </header>

                <div class="flex-1 flex overflow-hidden">
                    <div
                        v-if="viewMode !== 'preview'"
                        ref="editorPaneRef"
                        class="p-4 overflow-auto"
                        :class="viewMode === 'split' ? 'shrink-0' : 'flex-1'"
                        :style="viewMode === 'split' ? { width: `${editorWidth}px` } : {}"
                    >
                        <AppTextarea
                            v-model="form.content"
                            :placeholder="t('notes.markdown.content_placeholder')"
                            class="h-full w-full font-mono text-sm"
                            :rows="20"
                        />
                    </div>

                    <!-- Resize handle (split mode only). Drag to redistribute width. -->
                    <div
                        v-if="viewMode === 'split'"
                        class="w-1 shrink-0 cursor-col-resize bg-line hover:bg-accent-500/40 transition-colors"
                        :class="splitDragging ? 'bg-accent-500/60' : ''"
                        :title="t('notes.markdown.resize_handle')"
                        v-on:pointerdown="startSplitResize"
                    />

                    <div
                        v-if="viewMode !== 'edit'"
                        class="flex-1 p-4 overflow-auto"
                    >
                        <NotePreview
                            :content="form.content"
                            :note-titles="notes"
                            v-on:wiki-link-click="onWikiLinkClick"
                            v-on:checkbox-toggle="onCheckboxToggle"
                        />
                    </div>
                </div>
            </div>

            <div v-else class="flex-1 flex items-center justify-center text-muted text-sm">
                <AppNoData
                    :title="t('notes.markdown.no_selection.title')"
                    :description="t('notes.markdown.no_selection.description')"
                    :icon="FileText"
                />
            </div>
        </section>

        <NoteSidePanel
            v-if="sidePanelOpen && selectedNote"
            :note-id="selectedId"
            :fetch-backlinks="api.backlinks"
            :fetch-unlinked-mentions="api.unlinkedMentions"
            v-on:close="sidePanelOpen = false"
            v-on:navigate="selectNote"
        />

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="!deleting"
            :title="t('notes.markdown.delete')"
            :icon="Trash2"
            v-on:close="cancelDelete"
        >
            <p class="text-sm text-primary">
                {{ t('notes.markdown.confirm_delete', { title: pendingDelete?.title || t('notes.markdown.untitled') }) }}
            </p>
            <p class="text-sm text-secondary mt-2">
                {{ t('notes.markdown.delete_warning') }}
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" :disabled="deleting" v-on:click="cancelDelete">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('notes.markdown.cancel') }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="deleting" v-on:click="confirmDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('notes.markdown.delete') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
