import { ref, computed } from "vue";
import { Pencil, Eye, Columns } from "lucide-vue-next";
import { useMarkdownNotesApi } from "@notes/backend/markdown/composables/useMarkdownNotesApi.js";
import { useNotesEditor } from "@notes/backend/markdown/composables/useNotesEditor.js";
import { useNoteTree } from "@notes/backend/markdown/composables/useNoteTree.js";
import { useNoteTagFilter } from "@notes/backend/markdown/composables/useNoteTagFilter.js";
import { useMarkdownTagsApi } from "@notes/backend/markdown/composables/useMarkdownTagsApi.js";
import { useNoteDragDrop } from "@notes/backend/markdown/composables/useNoteDragDrop.js";
import { useViewMode } from "@notes/backend/markdown/composables/useViewMode.js";
import { useResizable } from "@shared/composables/useResizable.js";
import { useRelativeTime } from "@shared/composables/useRelativeTime.js";
import { useAutoSaveStatusDisplay } from "@shared/composables/useAutoSaveStatusDisplay.js";

/**
 * Page-level composition for the Markdown notes editor. Wires together
 * all the smaller domain composables (API client, editor state, tree
 * filter, tag manager, drag-drop, view mode, auto-save status, …) and
 * exposes a single flat bag that the SFC binds to its template.
 *
 * Keeping the orchestration here lets `MarkdownNotesApp.vue` stay pure
 * presentation, in the same shape as `NoteGraph.vue` / `NoteEditor.vue`.
 *
 * @param {object} props - same shape MarkdownNotesApp receives as
 *   defineProps: the eleven backend paths + the initial flat note list.
 * @param {(key: string) => string} t - vue-i18n's `t`
 */
export function useMarkdownNotesPage(props, t) {
    const api = useMarkdownNotesApi(props);
    const tagsApi = useMarkdownTagsApi(props);

    const editor = useNotesEditor({ api, initialNotes: props.notes });
    const {
        notes,
        selectedId,
        selectedNote,
        form,
        saving,
        deleting,
        saveStatus,
        lastSavedAt,
        selectNote,
        createNote,
        pendingDelete,
        requestDelete,
        cancelDelete,
        confirmDelete,
        onWikiLinkClick,
        onCheckboxToggle,
        refreshList,
        reloadCurrent,
    } = editor;

    // ── UI-only state ──────────────────────────────────────────────
    const sidePanelOpen = ref(false);
    const graphOpen = ref(false);
    const tagManagerOpen = ref(false);
    const treeQuery = ref("");

    // ── Tags + tree ────────────────────────────────────────────────
    const {
        availableTags,
        selectedTags,
        toggleTag,
        clearTags,
        pruneMissingTags,
    } = useNoteTagFilter(notes);
    const { tree } = useNoteTree(notes, treeQuery, selectedTags);

    /**
     * After a global tag rename / merge / delete, refresh the flat
     * note list, drop any selected-filter tags that vanished, and
     * reload the currently open note so its tags reflect the rewrite.
     */
    async function onTagsChanged() {
        await reloadCurrent();
        pruneMissingTags();
    }

    // ── Drag-drop ──────────────────────────────────────────────────
    // Drag-drop hierarchy editing is disabled while a filter is active,
    // since the visible tree is then a subset of the real one and a
    // drop would target a node hidden behind the filter.
    const dragEnabled = computed(
        () => treeQuery.value.trim() === "" && selectedTags.value.length === 0,
    );
    const dragDrop = useNoteDragDrop({ api, refreshList });

    // ── View mode (edit / split / preview) ─────────────────────────
    const { mode: viewMode } = useViewMode();
    const viewModeOptions = [
        { value: "edit", icon: Pencil, label: t("notes.markdown.view.edit") },
        {
            value: "split",
            icon: Columns,
            label: t("notes.markdown.view.split"),
        },
        {
            value: "preview",
            icon: Eye,
            label: t("notes.markdown.view.preview"),
        },
    ];

    // ── Auto-save status display ───────────────────────────────────
    const { relative: lastSavedRelative } = useRelativeTime(lastSavedAt);
    const { display: saveStatusDisplay } = useAutoSaveStatusDisplay(saveStatus);

    // ── Editor / preview split-pane resize ─────────────────────────
    const editorPaneRef = ref(null);
    const {
        size: editorWidth,
        startResize: startSplitResize,
        dragging: splitDragging,
    } = useResizable({
        key: "aurora.notes.markdown.editorWidth",
        defaultValue: 540,
        min: 240,
        max: 1200,
        axis: "x",
        getOrigin: () => editorPaneRef.value,
    });

    /**
     * Open the graph node-click handler — close the modal and switch
     * the editor to the picked note. Defined here so the SFC can bind
     * it as a single function rather than an inline lambda.
     */
    async function navigateFromGraph(id) {
        graphOpen.value = false;
        await selectNote(id);
    }

    return {
        // backend
        api,
        tagsApi,

        // editor domain
        notes,
        selectedId,
        selectedNote,
        form,
        saving,
        deleting,
        saveStatus,
        lastSavedAt,
        pendingDelete,
        selectNote,
        createNote,
        requestDelete,
        cancelDelete,
        confirmDelete,
        onWikiLinkClick,
        onCheckboxToggle,

        // sidebar tree + tags
        tree,
        treeQuery,
        availableTags,
        selectedTags,
        toggleTag,
        clearTags,
        onTagsChanged,

        // overlays
        sidePanelOpen,
        graphOpen,
        tagManagerOpen,

        // drag-drop
        dragEnabled,
        ...dragDrop,

        // view mode
        viewMode,
        viewModeOptions,

        // auto-save status
        lastSavedRelative,
        saveStatusDisplay,

        // split-pane resize
        editorPaneRef,
        editorWidth,
        startSplitResize,
        splitDragging,

        // cross-component glue
        navigateFromGraph,
    };
}
