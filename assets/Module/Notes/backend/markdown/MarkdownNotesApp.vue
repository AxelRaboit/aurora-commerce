<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import { useMarkdownNotesApi } from '@notes/backend/markdown/composables/useMarkdownNotesApi.js';
import { useNoteTree } from '@notes/backend/markdown/composables/useNoteTree.js';
import { useViewMode } from '@notes/backend/markdown/composables/useViewMode.js';
import { toggleCheckboxInContent } from '@notes/backend/markdown/composables/markedExtensions/markedCheckboxes.js';
import NoteTreeItem from '@notes/backend/markdown/components/NoteTreeItem.vue';
import NotePreview from '@notes/backend/markdown/components/NotePreview.vue';
import NoteSidePanel from '@notes/backend/markdown/components/NoteSidePanel.vue';
import AppButton from '@shared/components/action/AppButton.vue';
import AppIconButton from '@shared/components/action/AppIconButton.vue';
import AppInput from '@shared/components/form/AppInput.vue';
import AppTextarea from '@shared/components/form/AppTextarea.vue';
import AppNoData from '@shared/components/feedback/AppNoData.vue';
import { Plus, Save, Trash2, FileText, Pencil, Eye, Columns, PanelRightOpen, PanelRightClose } from 'lucide-vue-next';

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
const { mode: viewMode } = useViewMode();

const notes = ref([...props.notes]);
const selectedId = ref(null);
const form = ref({ title: '', content: '', tags: [] });
const saving = ref(false);
const deleting = ref(false);
const sidePanelOpen = ref(false);

const { tree } = useNoteTree(notes);

const selectedNote = computed(() => notes.value.find((n) => n.id === selectedId.value) || null);
const isDirty = computed(() => {
    if (!selectedNote.value) return false;
    return (
        (selectedNote.value.title || '') !== form.value.title ||
        (selectedNote.value.content || '') !== form.value.content
    );
});

async function refreshList() {
    const { ok, payload } = await api.list();
    if (ok) {
        notes.value = payload.notes;
    }
}

async function selectNote(id) {
    selectedId.value = id;
    const { ok, payload } = await api.show(id);
    if (!ok) {
        toast.error(t('notes.markdown.errors.load_failed'));
        return;
    }
    form.value = {
        title: payload.note.title ?? '',
        content: payload.note.content ?? '',
        tags: payload.note.tags ?? [],
    };
}

async function createNote(parentId = null) {
    const { ok, payload } = await api.create({ parentId, title: '', content: '' });
    if (!ok) {
        toast.error(t('notes.markdown.errors.create_failed'));
        return;
    }
    await refreshList();
    await selectNote(payload.note.id);
}

async function saveSelected() {
    if (!selectedNote.value) return;
    saving.value = true;
    try {
        const { ok, payload } = await api.update(selectedNote.value.id, {
            parentId: selectedNote.value.parentId,
            title: form.value.title,
            content: form.value.content,
            tags: form.value.tags,
        });
        if (!ok) {
            toast.error(t('notes.markdown.errors.save_failed'));
            return;
        }
        await refreshList();
        await selectNote(payload.note.id);
        toast.success(t('notes.markdown.saved'));
    } finally {
        saving.value = false;
    }
}

async function deleteSelected() {
    if (!selectedNote.value) return;
    if (!window.confirm(t('notes.markdown.confirm_delete', { title: selectedNote.value.title || t('notes.markdown.untitled') }))) {
        return;
    }
    deleting.value = true;
    try {
        const { ok } = await api.remove(selectedNote.value.id);
        if (!ok) {
            toast.error(t('notes.markdown.errors.delete_failed'));
            return;
        }
        selectedId.value = null;
        form.value = { title: '', content: '', tags: [] };
        await refreshList();
    } finally {
        deleting.value = false;
    }
}

/**
 * Wiki-link click in the preview pane. If the target title resolves to a
 * sibling note, navigate to it (after warning on unsaved changes).
 */
async function onWikiLinkClick({ noteTitle, matchedId }) {
    if (matchedId === null) {
        toast.info(t('notes.markdown.wiki_link_not_found', { title: noteTitle }));
        return;
    }
    if (matchedId === selectedId.value) return;
    if (isDirty.value && !window.confirm(t('notes.markdown.confirm_discard_changes'))) {
        return;
    }
    await selectNote(matchedId);
}

/**
 * Interactive checkbox toggle in the preview pane. Updates the source
 * markdown, then auto-saves so the new state is durable.
 */
async function onCheckboxToggle(index) {
    form.value.content = toggleCheckboxInContent(form.value.content, index);
    await saveSelected();
}

onMounted(() => {
    if (selectedId.value === null && notes.value.length > 0) {
        selectNote(notes.value[0].id);
    }
});

watch(isDirty, (dirty) => {
    if (dirty) {
        window.addEventListener('beforeunload', beforeUnloadHandler);
    } else {
        window.removeEventListener('beforeunload', beforeUnloadHandler);
    }
});
function beforeUnloadHandler(event) {
    event.preventDefault();
    event.returnValue = '';
}
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

            <div class="flex-1 overflow-auto p-2">
                <ul v-if="tree.length > 0" class="space-y-0.5">
                    <NoteTreeItem
                        v-for="node in tree"
                        :key="node.id"
                        :node="node"
                        :selected-id="selectedId"
                        v-on:select="selectNote"
                        v-on:create-child="createNote"
                    />
                </ul>
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

                    <!-- View mode toggle (edit / split / preview) -->
                    <div class="inline-flex rounded-md border border-line overflow-hidden">
                        <button
                            v-for="opt in [
                                { value: 'edit', icon: Pencil, label: t('notes.markdown.view.edit') },
                                { value: 'split', icon: Columns, label: t('notes.markdown.view.split') },
                                { value: 'preview', icon: Eye, label: t('notes.markdown.view.preview') },
                            ]"
                            :key="opt.value"
                            type="button"
                            class="px-2.5 py-1.5 text-sm transition-colors"
                            :class="viewMode === opt.value
                                ? 'bg-accent-100 dark:bg-accent-900/30 text-primary'
                                : 'bg-surface text-muted hover:text-secondary'"
                            :title="opt.label"
                            v-on:click="viewMode = opt.value"
                        >
                            <component :is="opt.icon" class="w-4 h-4" :stroke-width="2" />
                        </button>
                    </div>

                    <AppButton
                        variant="primary"
                        size="md"
                        :disabled="!isDirty || saving"
                        :loading="saving"
                        v-on:click="saveSelected"
                    >
                        <Save class="w-4 h-4" :stroke-width="2" />
                        <span>{{ t('notes.markdown.save') }}</span>
                    </AppButton>
                    <AppButton
                        variant="danger"
                        size="md"
                        :loading="deleting"
                        v-on:click="deleteSelected"
                    >
                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                        <span class="sr-only">{{ t('notes.markdown.delete') }}</span>
                    </AppButton>
                </header>

                <div class="flex-1 flex overflow-hidden">
                    <div
                        v-if="viewMode !== 'preview'"
                        class="flex-1 p-4 overflow-auto"
                        :class="viewMode === 'split' ? 'border-r border-line' : ''"
                    >
                        <AppTextarea
                            v-model="form.content"
                            :placeholder="t('notes.markdown.content_placeholder')"
                            class="h-full w-full font-mono text-sm"
                            :rows="20"
                        />
                    </div>

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
    </div>
</template>
