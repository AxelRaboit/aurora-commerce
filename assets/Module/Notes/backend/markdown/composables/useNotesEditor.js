import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { toggleCheckboxInContent } from "./markedExtensions/markedCheckboxes.js";

/**
 * State + actions for the Markdown notes editor.
 *
 * Owns the flat note list, the selected id, the dirty-tracked form, and all
 * server-roundtripping actions. Keeps the SFC focused on presentation.
 *
 * @param {object} options
 * @param {object} options.api          - useMarkdownNotesApi() instance
 * @param {Array}  options.initialNotes - flat list passed in via props
 */
export function useNotesEditor({ api, initialNotes }) {
    const { t } = useI18n();

    const notes = ref([...initialNotes]);
    const selectedId = ref(null);
    const form = ref({ title: "", content: "", tags: [] });
    const saving = ref(false);
    const deleting = ref(false);
    const pendingDelete = ref(null);

    const selectedNote = computed(
        () => notes.value.find((n) => n.id === selectedId.value) ?? null,
    );

    const isDirty = computed(() => {
        if (!selectedNote.value) return false;
        return (
            (selectedNote.value.title || "") !== form.value.title ||
            (selectedNote.value.content || "") !== form.value.content
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
            toast.error(t("notes.markdown.errors.load_failed"));
            return;
        }
        form.value = {
            title: payload.note.title ?? "",
            content: payload.note.content ?? "",
            tags: payload.note.tags ?? [],
        };
    }

    async function createNote(parentId = null) {
        const { ok, payload } = await api.create({
            parentId,
            title: "",
            content: "",
        });
        if (!ok) {
            toast.error(t("notes.markdown.errors.create_failed"));
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
                toast.error(t("notes.markdown.errors.save_failed"));
                return;
            }
            await refreshList();
            await selectNote(payload.note.id);
            toast.success(t("notes.markdown.saved"));
        } finally {
            saving.value = false;
        }
    }

    /**
     * Open the delete confirmation modal for the currently selected note.
     * The actual server call lives in `confirmDelete()` so the UI can
     * surface a styled modal instead of the native window.confirm.
     */
    function requestDelete() {
        if (!selectedNote.value) return;
        pendingDelete.value = selectedNote.value;
    }

    function cancelDelete() {
        pendingDelete.value = null;
    }

    async function confirmDelete() {
        if (!pendingDelete.value || deleting.value) return;
        const targetId = pendingDelete.value.id;
        deleting.value = true;
        try {
            const { ok } = await api.remove(targetId);
            if (!ok) {
                toast.error(t("notes.markdown.errors.delete_failed"));
                return;
            }
            if (selectedId.value === targetId) {
                selectedId.value = null;
                form.value = { title: "", content: "", tags: [] };
            }
            pendingDelete.value = null;
            await refreshList();
        } finally {
            deleting.value = false;
        }
    }

    /**
     * Wiki-link click in the preview pane. If the target title resolves to
     * an existing note, navigate to it — after warning on unsaved changes.
     */
    async function onWikiLinkClick({ noteTitle, matchedId }) {
        if (matchedId === null) {
            toast.info(
                t("notes.markdown.wiki_link_not_found", { title: noteTitle }),
            );
            return;
        }
        if (matchedId === selectedId.value) return;
        if (
            isDirty.value &&
            !window.confirm(t("notes.markdown.confirm_discard_changes"))
        ) {
            return;
        }
        await selectNote(matchedId);
    }

    /**
     * Interactive checkbox toggle in the preview pane. Mutates the source
     * markdown then auto-saves so the new state is durable server-side.
     */
    async function onCheckboxToggle(index) {
        form.value.content = toggleCheckboxInContent(form.value.content, index);
        await saveSelected();
    }

    // ── Lifecycle ──────────────────────────────────────────────────────────
    onMounted(() => {
        if (selectedId.value === null && notes.value.length > 0) {
            selectNote(notes.value[0].id);
        }
    });

    function beforeUnloadHandler(event) {
        event.preventDefault();
        event.returnValue = "";
    }

    watch(isDirty, (dirty) => {
        if (dirty) {
            window.addEventListener("beforeunload", beforeUnloadHandler);
        } else {
            window.removeEventListener("beforeunload", beforeUnloadHandler);
        }
    });

    onBeforeUnmount(() => {
        window.removeEventListener("beforeunload", beforeUnloadHandler);
    });

    return {
        // state
        notes,
        selectedId,
        selectedNote,
        form,
        isDirty,
        saving,
        deleting,
        pendingDelete,
        // actions
        refreshList,
        selectNote,
        createNote,
        saveSelected,
        requestDelete,
        cancelDelete,
        confirmDelete,
        onWikiLinkClick,
        onCheckboxToggle,
    };
}
