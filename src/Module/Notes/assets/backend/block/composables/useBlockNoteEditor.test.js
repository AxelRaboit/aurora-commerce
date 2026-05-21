import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { defineComponent, h, nextTick } from "vue";
import { mount } from "@vue/test-utils";

vi.mock("vue-i18n", () => ({
    useI18n: () => ({ t: (key) => key }),
}));

const toastErrorMock = vi.fn();
vi.mock("vue-sonner", () => ({
    toast: { error: toastErrorMock, success: vi.fn() },
}));

const { useBlockNoteEditor } = await import("./useBlockNoteEditor.js");

function makeApi({
    listNotes = [],
    showNote = null,
    createNote: createNoteResp = null,
    updateNote = null,
    removeOk = true,
} = {}) {
    return {
        list: vi
            .fn()
            .mockResolvedValue({ ok: true, payload: { notes: listNotes } }),
        show: vi.fn().mockImplementation((id) =>
            Promise.resolve({
                ok: true,
                payload: {
                    note: showNote ?? { id, title: "", tags: [], blocks: [] },
                },
            }),
        ),
        create: vi.fn().mockResolvedValue({
            ok: true,
            payload: {
                note: createNoteResp ?? {
                    id: 99,
                    title: "",
                    tags: [],
                    blocks: [],
                    parentId: null,
                },
            },
        }),
        update: vi.fn().mockResolvedValue({
            ok: true,
            payload: { note: updateNote },
        }),
        remove: vi.fn().mockResolvedValue({ ok: removeOk, payload: {} }),
    };
}

/**
 * Host the composable inside a real Vue component so `onMounted`,
 * `onBeforeUnmount`, watch and useAutoSave's onBeforeUnmount fire.
 */
function mountEditor(opts) {
    let captured;
    const Comp = defineComponent({
        setup() {
            captured = useBlockNoteEditor(opts);
            return () => h("div");
        },
    });
    const wrapper = mount(Comp);
    return { wrapper, editor: captured };
}

beforeEach(() => {
    toastErrorMock.mockReset();
    // beforeunload listeners live on `window`; clear any leftovers.
    vi.spyOn(window, "addEventListener");
    vi.spyOn(window, "removeEventListener");
});

afterEach(() => {
    vi.restoreAllMocks();
});

describe("useBlockNoteEditor", () => {
    it("auto-selects the first note on mount when notes are present", async () => {
        const api = makeApi({
            showNote: { id: 1, title: "First", tags: ["x"], blocks: [] },
        });
        const initialNotes = [{ id: 1, title: "First", tags: ["x"] }];
        const { editor } = mountEditor({ api, initialNotes });

        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        expect(api.show).toHaveBeenCalledWith(1);
        expect(editor.selectedId.value).toBe(1);
        expect(editor.form.value.title).toBe("First");
        expect(editor.form.value.tags).toEqual(["x"]);
    });

    it("does not call show when initialNotes is empty", async () => {
        const api = makeApi();
        const { editor } = mountEditor({ api, initialNotes: [] });

        await nextTick();
        await Promise.resolve();

        expect(api.show).not.toHaveBeenCalled();
        expect(editor.selectedId.value).toBeNull();
    });

    it("isDirty stays false right after loading a note", async () => {
        const api = makeApi({
            showNote: { id: 1, title: "T", tags: ["a"], blocks: [] },
        });
        const { editor } = mountEditor({
            api,
            initialNotes: [{ id: 1, title: "T", tags: ["a"] }],
        });

        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        expect(editor.isDirty.value).toBe(false);
    });

    it("isDirty flips true when the title changes", async () => {
        const api = makeApi({
            showNote: { id: 1, title: "T", tags: [], blocks: [] },
        });
        const { editor } = mountEditor({
            api,
            initialNotes: [{ id: 1, title: "T", tags: [] }],
        });

        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        editor.form.value.title = "Renamed";
        await nextTick();

        expect(editor.isDirty.value).toBe(true);
    });

    it("isDirty flips true when blocks change", async () => {
        const api = makeApi({
            showNote: { id: 1, title: "T", tags: [], blocks: [] },
        });
        const { editor } = mountEditor({
            api,
            initialNotes: [{ id: 1, title: "T", tags: [] }],
        });

        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        editor.setBlocks([
            { id: "b1", type: "paragraph", data: { text: "hi" } },
        ]);
        await nextTick();

        expect(editor.isDirty.value).toBe(true);
    });

    it("setBlocks coerces null to an empty array", async () => {
        const api = makeApi({
            showNote: { id: 1, title: "T", tags: [], blocks: [] },
        });
        const { editor } = mountEditor({
            api,
            initialNotes: [{ id: 1, title: "T", tags: [] }],
        });

        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        editor.setBlocks(null);
        expect(editor.form.value.blocks).toEqual([]);
    });

    it("createNote refreshes the list and selects the new note", async () => {
        const api = makeApi({
            createNote: {
                id: 99,
                title: "",
                tags: [],
                blocks: [],
                parentId: null,
            },
        });
        // After create, list returns the new note alongside the old.
        api.list.mockResolvedValueOnce({
            ok: true,
            payload: { notes: [{ id: 99, title: "", tags: [] }] },
        });

        const { editor } = mountEditor({ api, initialNotes: [] });

        await nextTick();
        await editor.createNote(null);

        expect(api.create).toHaveBeenCalledWith({
            parentId: null,
            title: "",
            tags: [],
            blocks: [],
        });
        expect(api.list).toHaveBeenCalled();
        expect(api.show).toHaveBeenCalledWith(99);
        expect(editor.selectedId.value).toBe(99);
    });

    it("createNote toasts on failure and does not refresh", async () => {
        const api = makeApi();
        api.create.mockResolvedValueOnce({ ok: false, payload: {} });
        const { editor } = mountEditor({ api, initialNotes: [] });

        await nextTick();
        await editor.createNote(null);

        expect(toastErrorMock).toHaveBeenCalledWith(
            "notes.block.errors.create_failed",
        );
        expect(api.show).not.toHaveBeenCalled();
        expect(editor.selectedId.value).toBeNull();
    });

    it("selectNote toasts on load failure", async () => {
        const api = makeApi();
        api.show.mockResolvedValueOnce({ ok: false, payload: {} });
        const { editor } = mountEditor({
            api,
            initialNotes: [{ id: 1, title: "T", tags: [] }],
        });

        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        expect(toastErrorMock).toHaveBeenCalledWith(
            "notes.block.errors.load_failed",
        );
    });

    it("requestDelete + confirmDelete deletes via api.remove and clears selection", async () => {
        const api = makeApi({
            showNote: { id: 1, title: "T", tags: [], blocks: [] },
        });
        api.list.mockResolvedValueOnce({
            ok: true,
            payload: { notes: [{ id: 1, title: "T", tags: [] }] },
        });
        api.list.mockResolvedValueOnce({ ok: true, payload: { notes: [] } });

        const { editor } = mountEditor({
            api,
            initialNotes: [{ id: 1, title: "T", tags: [] }],
        });
        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        editor.requestDelete();
        expect(editor.pendingDelete.value?.id).toBe(1);

        await editor.confirmDelete();

        expect(api.remove).toHaveBeenCalledWith(1);
        expect(editor.selectedId.value).toBeNull();
        expect(editor.pendingDelete.value).toBeNull();
    });

    it("confirmDelete toasts on remove failure and keeps the selection", async () => {
        const api = makeApi({
            showNote: { id: 1, title: "T", tags: [], blocks: [] },
            removeOk: false,
        });
        const { editor } = mountEditor({
            api,
            initialNotes: [{ id: 1, title: "T", tags: [] }],
        });
        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        editor.requestDelete();
        await editor.confirmDelete();

        expect(toastErrorMock).toHaveBeenCalledWith(
            "notes.block.errors.delete_failed",
        );
        expect(editor.selectedId.value).toBe(1);
    });

    it("cancelDelete clears the pending target without calling remove", async () => {
        const api = makeApi({
            showNote: { id: 1, title: "T", tags: [], blocks: [] },
        });
        const { editor } = mountEditor({
            api,
            initialNotes: [{ id: 1, title: "T", tags: [] }],
        });
        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        editor.requestDelete();
        editor.cancelDelete();

        expect(editor.pendingDelete.value).toBeNull();
        expect(api.remove).not.toHaveBeenCalled();
    });

    it("refreshList replaces notes from the api payload", async () => {
        const api = makeApi();
        api.list.mockResolvedValueOnce({
            ok: true,
            payload: { notes: [{ id: 2, title: "X", tags: [] }] },
        });
        const { editor } = mountEditor({ api, initialNotes: [] });

        await nextTick();
        await editor.refreshList();

        expect(editor.notes.value).toEqual([{ id: 2, title: "X", tags: [] }]);
    });

    it("seeds extra fields with defaults and treats their change as dirty", async () => {
        const api = makeApi({
            showNote: {
                id: 1,
                title: "T",
                tags: [],
                blocks: [],
                color: "blue",
            },
        });
        const { editor } = mountEditor({
            api,
            initialNotes: [{ id: 1, title: "T", tags: [] }],
            extraFields: { color: { default: "red" } },
        });

        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        expect(editor.form.value.color).toBe("blue");
        expect(editor.isDirty.value).toBe(false);

        editor.form.value.color = "green";
        await nextTick();
        expect(editor.isDirty.value).toBe(true);
    });
});
