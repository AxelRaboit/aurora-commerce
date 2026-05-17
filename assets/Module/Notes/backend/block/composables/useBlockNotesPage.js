import { ref, computed, watch } from "vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { useMediaQuery } from "@/shared/composables/useMediaQuery.js";
import { useBlockNotesApi } from "@notes/backend/block/composables/useBlockNotesApi.js";
import { useBlockTagsApi } from "@notes/backend/block/composables/useBlockTagsApi.js";
import { useBlockNoteEditor } from "@notes/backend/block/composables/useBlockNoteEditor.js";
import { useBlockNoteTree } from "@notes/backend/block/composables/useBlockNoteTree.js";
import { useBlockNoteTagFilter } from "@notes/backend/block/composables/useBlockNoteTagFilter.js";
import { useBlockNoteDragDrop } from "@notes/backend/block/composables/useBlockNoteDragDrop.js";
import { useRelativeTime } from "@shared/composables/useRelativeTime.js";
import { useAutoSaveStatusDisplay } from "@shared/composables/useAutoSaveStatusDisplay.js";

/**
 * Page-level composition for the Block notes editor. Block editing is
 * delegated to Editor.js (`NotesBlockEditor.vue`) — this composable only
 * orchestrates the surrounding UI: tree + tag filter + autosave status.
 */
export function useBlockNotesPage(props, t) {
    const api = useBlockNotesApi(props);
    const tagsApi = useBlockTagsApi(props);

    const editor = useBlockNoteEditor({
        api,
        initialNotes: props.notes,
        extraFields: props.extraFields ?? {},
    });
    const {
        notes,
        selectedId,
        selectedNote,
        form,
        saving,
        deleting,
        saveStatus,
        lastSavedAt,
        selectNote: selectNoteRaw,
        createNote: createNoteRaw,
        pendingDelete,
        requestDelete,
        cancelDelete,
        confirmDelete,
        refreshList,
        reloadCurrent,
        setBlocks,
    } = editor;

    const tagManagerOpen = ref(false);
    const treeQuery = ref("");

    const { matches: isMobile } = useMediaQuery("(max-width: 767px)");
    const sidebarOpen = ref(!isMobile.value);
    watch(isMobile, (mobile) => {
        sidebarOpen.value = !mobile;
    });

    const {
        availableTags,
        selectedTags,
        toggleTag,
        clearTags,
        pruneMissingTags,
    } = useBlockNoteTagFilter(notes);

    const contentMatchIds = ref(new Set());
    const contentSearchLoading = ref(false);
    const runContentSearch = useDebounce(async (query) => {
        if (!query) {
            contentMatchIds.value = new Set();
            contentSearchLoading.value = false;
            return;
        }
        const { ok, payload } = await api.searchContent(query);
        contentMatchIds.value = new Set(
            ok ? (payload.ids ?? []).map((id) => Number(id)) : [],
        );
        contentSearchLoading.value = false;
    }, 300);
    watch(treeQuery, (q) => {
        const trimmed = q.trim();
        if (trimmed === "") {
            contentMatchIds.value = new Set();
            contentSearchLoading.value = false;
            return;
        }
        contentSearchLoading.value = true;
        runContentSearch(trimmed);
    });

    const { tree } = useBlockNoteTree(
        notes,
        treeQuery,
        selectedTags,
        contentMatchIds,
    );

    async function onTagsChanged() {
        await reloadCurrent();
        pruneMissingTags();
    }

    const dragEnabled = computed(
        () => treeQuery.value.trim() === "" && selectedTags.value.length === 0,
    );
    const dragDrop = useBlockNoteDragDrop({ api, refreshList });

    async function selectNote(id) {
        await selectNoteRaw(id);
        if (isMobile.value) sidebarOpen.value = false;
    }
    async function createNote(parentId) {
        await createNoteRaw(parentId);
        if (isMobile.value) sidebarOpen.value = false;
    }

    const { relative: lastSavedRelative } = useRelativeTime(lastSavedAt);
    const { display: saveStatusDisplay } = useAutoSaveStatusDisplay(saveStatus);

    return {
        isMobile,
        sidebarOpen,
        api,
        tagsApi,
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
        setBlocks,
        tree,
        treeQuery,
        contentSearchLoading,
        availableTags,
        selectedTags,
        toggleTag,
        clearTags,
        onTagsChanged,
        tagManagerOpen,
        dragEnabled,
        ...dragDrop,
        lastSavedRelative,
        saveStatusDisplay,
    };
}
