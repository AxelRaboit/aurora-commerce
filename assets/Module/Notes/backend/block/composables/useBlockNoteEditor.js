import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useAutoSave } from "@/shared/composables/useAutoSave.js";

/**
 * State + actions for the Block-notes editor.
 *
 * The block list itself is owned by Editor.js (`NotesBlockEditor.vue`):
 * this composable just stores the current Editor.js JSON payload on
 * `form.blocks` and pushes it back into the editor on note switch via
 * `setBlocks`. Auto-save fires on any mutation — title, tags, or any
 * change emitted by Editor.js.
 */
export function useBlockNoteEditor({ api, initialNotes, extraFields = {} }) {
    const { t } = useI18n();

    const extraKeys = Object.keys(extraFields);
    function extraDefaults() {
        return Object.fromEntries(
            extraKeys.map((key) => [key, extraFields[key]?.default ?? null]),
        );
    }
    function pickExtras(source) {
        return Object.fromEntries(
            extraKeys.map((key) => [
                key,
                source?.[key] ?? extraFields[key]?.default ?? null,
            ]),
        );
    }

    const notes = ref([...initialNotes]);
    const selectedId = ref(null);
    const form = ref({
        title: "",
        tags: [],
        blocks: [],
        ...extraDefaults(),
    });
    const loadedSnapshot = ref(null);
    const saving = ref(false);
    const deleting = ref(false);
    const pendingDelete = ref(null);

    const selectedNote = computed(
        () => notes.value.find((n) => n.id === selectedId.value) ?? null,
    );

    // Editor.js emits blocks as `{id, type, data}` — Editor.js generates
    // the id itself and it's stable across edits. We just compare the
    // whole JSON: cheap and bulletproof for the typical note size.
    function blocksDirty(a, b) {
        return JSON.stringify(a ?? []) !== JSON.stringify(b ?? []);
    }

    const isDirty = computed(() => {
        if (!loadedSnapshot.value) return false;
        if (loadedSnapshot.value.title !== form.value.title) return true;
        const a = loadedSnapshot.value.tags;
        const b = form.value.tags ?? [];
        if (a.length !== b.length) return true;
        for (let i = 0; i < a.length; i++) {
            if (a[i] !== b[i]) return true;
        }
        if (blocksDirty(loadedSnapshot.value.blocks, form.value.blocks)) {
            return true;
        }
        for (const key of extraKeys) {
            if (loadedSnapshot.value[key] !== form.value[key]) return true;
        }
        return false;
    });

    async function refreshList() {
        const { ok, payload } = await api.list();
        if (ok) {
            notes.value = payload.notes;
        }
    }

    // Server returns blocks as Editor.js JSON (`{id?, type, data}`).
    // We pass the array through unchanged; Editor.js will fill in any
    // missing `id` on first render.
    function snapshotFromPayload(note) {
        return {
            title: note.title ?? "",
            tags: [...(note.tags ?? [])],
            blocks: (note.blocks ?? []).map((b) => ({
                ...b,
                data: { ...(b.data ?? {}) },
            })),
            ...pickExtras(note),
        };
    }

    async function selectNote(id) {
        await flushPendingSave();

        selectedId.value = id;
        const { ok, payload } = await api.show(id);
        if (!ok) {
            toast.error(t("notes.block.errors.load_failed"));
            return;
        }

        const snapshot = snapshotFromPayload(payload.note);
        loadedSnapshot.value = snapshot;
        form.value = {
            title: snapshot.title,
            tags: [...snapshot.tags],
            blocks: snapshot.blocks.map((b) => ({
                ...b,
                data: { ...(b.data ?? {}) },
            })),
            ...pickExtras(payload.note),
        };
        cancelAutoSave();
    }

    /** Called by NotesBlockEditor (v-model) on every Editor.js change. */
    function setBlocks(blocks) {
        form.value.blocks = blocks ?? [];
    }

    async function createNote(parentId = null) {
        const { ok, payload } = await api.create({
            parentId,
            title: "",
            tags: [],
            blocks: [],
        });
        if (!ok) {
            toast.error(t("notes.block.errors.create_failed"));
            return;
        }
        await refreshList();
        await selectNote(payload.note.id);
    }

    async function performSave() {
        if (!selectedNote.value) return true;

        saving.value = true;
        const noteId = selectedNote.value.id;
        const parentId = selectedNote.value.parentId;
        // Editor.js' own block ids are preserved in the payload — they
        // help Editor.js track block identity across renders. The server
        // stores the full Editor.js JSON shape verbatim.
        const blocksPayload = form.value.blocks.map((b) => ({
            ...(b.id ? { id: b.id } : {}),
            type: b.type,
            data: b.data ?? {},
        }));
        const snapshot = {
            title: form.value.title,
            tags: [...form.value.tags],
            blocks: blocksPayload,
            ...pickExtras(form.value),
        };

        try {
            const { ok, payload } = await api.update(noteId, {
                parentId,
                ...snapshot,
            });
            if (!ok) return false;

            // Refresh loadedSnapshot from the server response so isDirty
            // settles back to false. Don't reassign form.value.blocks —
            // that would churn the Vue keys mid-edit and re-mount every
            // block (cursor loss, image flicker). Same-shape comparison
            // via blocksDirty() is enough.
            if (payload.note) {
                loadedSnapshot.value = snapshotFromPayload(payload.note);
            } else {
                loadedSnapshot.value = {
                    ...snapshot,
                    blocks: snapshot.blocks.map((b) => ({
                        ...b,
                        data: { ...b.data },
                    })),
                };
            }

            const index = notes.value.findIndex((n) => n.id === noteId);
            if (index !== -1) {
                notes.value[index] = {
                    ...notes.value[index],
                    title: snapshot.title,
                    tags: snapshot.tags,
                };
            }
            return true;
        } finally {
            saving.value = false;
        }
    }

    const {
        saveStatus,
        lastSavedAt,
        schedule: scheduleAutoSave,
        flush: flushPendingSave,
        cancel: cancelAutoSave,
    } = useAutoSave({
        isDirty: () => isDirty.value,
        save: performSave,
        onError: () => toast.error(t("notes.block.errors.save_failed")),
    });

    function requestDelete(note = null) {
        const target = note ?? selectedNote.value;
        if (!target) return;
        pendingDelete.value = target;
    }

    function cancelDelete() {
        pendingDelete.value = null;
    }

    async function confirmDelete() {
        if (!pendingDelete.value || deleting.value) return;
        const targetId = pendingDelete.value.id;
        deleting.value = true;
        try {
            if (selectedId.value === targetId) {
                cancelAutoSave();
            }

            const { ok } = await api.remove(targetId);
            if (!ok) {
                toast.error(t("notes.block.errors.delete_failed"));
                return;
            }
            if (selectedId.value === targetId) {
                selectedId.value = null;
                loadedSnapshot.value = null;
                form.value = {
                    title: "",
                    tags: [],
                    blocks: [],
                    ...extraDefaults(),
                };
            }
            pendingDelete.value = null;
            await refreshList();
        } finally {
            deleting.value = false;
        }
    }

    async function reloadCurrent() {
        await refreshList();
        if (selectedId.value !== null) {
            await selectNote(selectedId.value);
        }
    }

    onMounted(() => {
        if (selectedId.value === null && notes.value.length > 0) {
            selectNote(notes.value[0].id);
        }
    });

    function beforeUnloadHandler(event) {
        event.preventDefault();
        event.returnValue = "";
    }

    watch(
        form,
        () => {
            if (!isDirty.value || saving.value) return;
            scheduleAutoSave();
        },
        { deep: true },
    );

    watch(isDirty, (dirty) => {
        if (
            dirty ||
            saveStatus.value === "saving" ||
            saveStatus.value === "pending"
        ) {
            window.addEventListener("beforeunload", beforeUnloadHandler);
        } else {
            window.removeEventListener("beforeunload", beforeUnloadHandler);
        }
    });

    onBeforeUnmount(() => {
        window.removeEventListener("beforeunload", beforeUnloadHandler);
    });

    return {
        notes,
        selectedId,
        selectedNote,
        form,
        isDirty,
        saving,
        deleting,
        pendingDelete,
        saveStatus,
        lastSavedAt,
        refreshList,
        reloadCurrent,
        selectNote,
        createNote,
        flushPendingSave,
        requestDelete,
        cancelDelete,
        confirmDelete,
        setBlocks,
    };
}
