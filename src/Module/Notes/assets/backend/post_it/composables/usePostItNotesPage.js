import { ref, computed, onMounted } from "vue";
import { usePostItNotesApi } from "./usePostItNotesApi.js";

export const POST_IT_COLORS = Object.freeze([
    "#FFEB3B", // yellow (default)
    "#FFCC80", // orange
    "#EF9A9A", // red
    "#A5D6A7", // green
    "#90CAF9", // blue
    "#CE93D8", // purple
    "#BCAAA4", // brown
    "#E0E0E0", // grey
]);

const DEBOUNCE_MS = 400;
const STAGGER_PX = 20;

/**
 * Orchestrates the post-it board page state: holds the notes list,
 * loading flag, palette popover, and exposes CRUD operations that
 * delegate to {@link usePostItNotesApi}. Save-on-edit is debounced so
 * the user's typing doesn't fire a request per keystroke.
 *
 * Delete uses a "pending → confirmed" pattern (cf. MarkdownNotesApp /
 * BlockNotesApp): {@link requestDelete} stages the note for deletion,
 * {@link confirmDelete} actually fires the API call. The consumer wires
 * `pendingDelete` to an `<AppModal>` and exposes confirm/cancel buttons.
 */
export function usePostItNotesPage(props) {
    const api = usePostItNotesApi(props);

    const notes = ref([]);
    const loading = ref(false);
    const palettePickerOpenFor = ref(null);
    const pendingDelete = ref(null);
    const deleting = ref(false);
    const searchQuery = ref("");

    const saveTimers = new Map();

    function extractNote(response) {
        return response.payload.note ?? response.payload.data?.note ?? null;
    }

    function extractNotes(response) {
        return response.payload.notes ?? response.payload.data?.notes ?? [];
    }

    async function loadNotes() {
        loading.value = true;
        try {
            const response = await api.list();
            if (response.ok) {
                notes.value = extractNotes(response);
            }
        } finally {
            loading.value = false;
        }
    }

    async function createNote() {
        const offset = (notes.value.length % 10) * STAGGER_PX;
        const response = await api.create({
            title: "",
            content: "",
            color: POST_IT_COLORS[0],
            positionX: 24 + offset,
            positionY: 24 + offset,
        });
        const created = extractNote(response);
        if (created) {
            notes.value.push(created);
        }
    }

    function scheduleSave(note) {
        const id = note.id;
        if (saveTimers.has(id)) {
            clearTimeout(saveTimers.get(id));
        }
        saveTimers.set(
            id,
            setTimeout(() => {
                saveTimers.delete(id);
                saveNote(note);
            }, DEBOUNCE_MS),
        );
    }

    async function saveNote(note) {
        await api.update(note.id, {
            title: note.title ?? "",
            content: note.content ?? "",
            color: note.color,
            positionX: note.positionX,
            positionY: note.positionY,
        });
    }

    async function persistMove(note) {
        await api.move(note.id, {
            positionX: note.positionX,
            positionY: note.positionY,
        });
    }

    async function persistResize(note) {
        await api.resize(note.id, {
            width: note.width,
            height: note.height,
        });
    }

    function requestDelete(note) {
        pendingDelete.value = note;
    }

    function cancelDelete() {
        if (deleting.value) return;
        pendingDelete.value = null;
    }

    async function confirmDelete() {
        const note = pendingDelete.value;
        if (!note || deleting.value) return;
        deleting.value = true;
        try {
            const response = await api.delete(note.id);
            if (response.ok) {
                notes.value = notes.value.filter((n) => n.id !== note.id);
                pendingDelete.value = null;
            }
        } finally {
            deleting.value = false;
        }
    }

    function setColor(note, color) {
        note.color = color;
        palettePickerOpenFor.value = null;
        scheduleSave(note);
    }

    function togglePalette(note) {
        palettePickerOpenFor.value =
            palettePickerOpenFor.value === note.id ? null : note.id;
    }

    const isEmpty = computed(() => !loading.value && notes.value.length === 0);

    /**
     * Notes filtered by the live search query — matches title or content
     * via case-insensitive substring. An empty query passes everything
     * through unchanged so consumers can always render this collection.
     */
    const filteredNotes = computed(() => {
        const q = searchQuery.value.trim().toLowerCase();
        if ("" === q) return notes.value;
        return notes.value.filter((note) => {
            const title = (note.title ?? "").toLowerCase();
            const content = (note.content ?? "").toLowerCase();
            return title.includes(q) || content.includes(q);
        });
    });

    const isFiltering = computed(() => searchQuery.value.trim() !== "");

    const hasNoMatches = computed(
        () =>
            !loading.value &&
            notes.value.length > 0 &&
            filteredNotes.value.length === 0,
    );

    onMounted(loadNotes);

    return {
        notes,
        loading,
        isEmpty,
        palettePickerOpenFor,
        pendingDelete,
        deleting,
        searchQuery,
        filteredNotes,
        isFiltering,
        hasNoMatches,
        loadNotes,
        createNote,
        scheduleSave,
        persistMove,
        persistResize,
        requestDelete,
        cancelDelete,
        confirmDelete,
        setColor,
        togglePalette,
    };
}
