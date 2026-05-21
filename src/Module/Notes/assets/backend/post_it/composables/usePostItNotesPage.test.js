import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

// Stub the api layer so the page composable is tested in isolation —
// real fetch behavior is covered by usePostItNotesApi.test.js.
const apiMethods = {
    list: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    move: vi.fn(),
    resize: vi.fn(),
    delete: vi.fn(),
};

vi.mock("./usePostItNotesApi.js", () => ({
    usePostItNotesApi: () => apiMethods,
}));

const { usePostItNotesPage, POST_IT_COLORS } =
    await import("./usePostItNotesPage.js");

const props = {
    listPath: "/api/post-it/list",
    createPath: "/api/post-it/create",
    updatePath: "/api/post-it/__id__/update",
    movePath: "/api/post-it/__id__/move",
    resizePath: "/api/post-it/__id__/resize",
    deletePath: "/api/post-it/__id__/delete",
};

function okResponse(payload) {
    return { ok: true, status: 200, payload };
}

function failResponse(payload = { success: false }) {
    return { ok: false, status: 400, payload };
}

beforeEach(() => {
    Object.values(apiMethods).forEach((m) => m.mockReset());
    // Loading is fired in onMounted — provide a default empty list so the
    // initial fetch doesn't throw when a test forgets to stub it.
    apiMethods.list.mockResolvedValue(okResponse({ notes: [] }));
});

afterEach(() => {
    vi.useRealTimers();
});

describe("usePostItNotesPage", () => {
    describe("color palette", () => {
        it("exports exactly 8 frozen hex codes starting with yellow", () => {
            expect(POST_IT_COLORS).toHaveLength(8);
            expect(POST_IT_COLORS[0]).toBe("#FFEB3B");
            expect(Object.isFrozen(POST_IT_COLORS)).toBe(true);
        });
    });

    describe("loadNotes", () => {
        // onMounted only fires inside a component setup — these tests call
        // loadNotes() manually, which is also the documented refresh API.
        it("populates notes from the api response", async () => {
            apiMethods.list.mockResolvedValue(
                okResponse({ notes: [{ id: 1, title: "first" }] }),
            );

            const { notes, loading, loadNotes } = usePostItNotesPage(props);
            await loadNotes();

            expect(loading.value).toBe(false);
            expect(notes.value).toEqual([{ id: 1, title: "first" }]);
        });

        it("isEmpty becomes true when load finishes with no notes", async () => {
            const { isEmpty, loadNotes } = usePostItNotesPage(props);
            await loadNotes();

            expect(isEmpty.value).toBe(true);
        });

        it("leaves notes untouched when the api returns ok=false", async () => {
            apiMethods.list.mockResolvedValue(failResponse());
            const { notes, loadNotes } = usePostItNotesPage(props);

            await loadNotes();

            expect(notes.value).toEqual([]);
        });
    });

    describe("create", () => {
        it("pushes the new note returned by the api onto the list", async () => {
            apiMethods.create.mockResolvedValue(
                okResponse({ note: { id: 42, title: "new" } }),
            );
            const { notes, createNote } = usePostItNotesPage(props);

            await createNote();

            expect(notes.value).toContainEqual({ id: 42, title: "new" });
        });

        it("staggers positionX/Y so successive new notes don't stack", async () => {
            apiMethods.list.mockResolvedValue(
                okResponse({ notes: [{ id: 1 }, { id: 2 }] }),
            );
            apiMethods.create.mockResolvedValue(
                okResponse({ note: { id: 99 } }),
            );

            const { createNote, loadNotes } = usePostItNotesPage(props);
            await loadNotes();

            await createNote();

            // 2 existing notes → 3rd insertion offset = (2 % 10) * 20 = 40px
            // on both axes (plus the 24px base padding).
            expect(apiMethods.create).toHaveBeenCalledWith({
                title: "",
                content: "",
                color: "#FFEB3B",
                positionX: 64,
                positionY: 64,
            });
        });
    });

    describe("scheduleSave (debounced)", () => {
        it("flushes a single update call after the 400ms window", async () => {
            vi.useFakeTimers();
            apiMethods.update.mockResolvedValue(okResponse({ note: {} }));

            const { scheduleSave } = usePostItNotesPage(props);
            await vi.advanceTimersByTimeAsync(0); // settle onMounted

            const note = {
                id: 1,
                title: "T",
                content: "C",
                color: "#FFEB3B",
                positionX: 0,
                positionY: 0,
            };
            scheduleSave(note);
            scheduleSave(note);
            scheduleSave(note);

            // No call yet — still inside the debounce window.
            expect(apiMethods.update).not.toHaveBeenCalled();

            await vi.advanceTimersByTimeAsync(400);

            expect(apiMethods.update).toHaveBeenCalledOnce();
            expect(apiMethods.update).toHaveBeenCalledWith(1, {
                title: "T",
                content: "C",
                color: "#FFEB3B",
                positionX: 0,
                positionY: 0,
            });
        });
    });

    describe("persistMove / persistResize", () => {
        it("persistMove forwards positionX/Y to the api", async () => {
            apiMethods.move.mockResolvedValue(okResponse({}));
            const { persistMove } = usePostItNotesPage(props);
            await Promise.resolve();

            await persistMove({ id: 7, positionX: 200, positionY: 100 });

            expect(apiMethods.move).toHaveBeenCalledWith(7, {
                positionX: 200,
                positionY: 100,
            });
        });

        it("persistResize forwards width/height to the api", async () => {
            apiMethods.resize.mockResolvedValue(okResponse({}));
            const { persistResize } = usePostItNotesPage(props);
            await Promise.resolve();

            await persistResize({ id: 7, width: 300, height: 250 });

            expect(apiMethods.resize).toHaveBeenCalledWith(7, {
                width: 300,
                height: 250,
            });
        });
    });

    describe("pending-delete modal", () => {
        it("requestDelete stages the note without calling the api", async () => {
            const { pendingDelete, requestDelete } = usePostItNotesPage(props);
            await Promise.resolve();

            requestDelete({ id: 5 });

            expect(pendingDelete.value).toEqual({ id: 5 });
            expect(apiMethods.delete).not.toHaveBeenCalled();
        });

        it("cancelDelete clears the staged note", async () => {
            const { pendingDelete, requestDelete, cancelDelete } =
                usePostItNotesPage(props);
            await Promise.resolve();

            requestDelete({ id: 5 });
            cancelDelete();

            expect(pendingDelete.value).toBeNull();
        });

        it("confirmDelete removes the note from the list on api success", async () => {
            apiMethods.list.mockResolvedValue(
                okResponse({ notes: [{ id: 1 }, { id: 2 }] }),
            );
            apiMethods.delete.mockResolvedValue(okResponse({}));

            const {
                notes,
                requestDelete,
                confirmDelete,
                pendingDelete,
                loadNotes,
            } = usePostItNotesPage(props);
            await loadNotes();

            requestDelete({ id: 1 });
            await confirmDelete();

            expect(apiMethods.delete).toHaveBeenCalledWith(1);
            expect(notes.value.map((n) => n.id)).toEqual([2]);
            expect(pendingDelete.value).toBeNull();
        });

        it("confirmDelete does NOT remove the note when the api fails", async () => {
            apiMethods.list.mockResolvedValue(
                okResponse({ notes: [{ id: 1 }] }),
            );
            apiMethods.delete.mockResolvedValue(failResponse());

            const {
                notes,
                requestDelete,
                confirmDelete,
                pendingDelete,
                loadNotes,
            } = usePostItNotesPage(props);
            await loadNotes();

            requestDelete({ id: 1 });
            await confirmDelete();

            // Note still in list, modal still open — user can retry.
            expect(notes.value).toHaveLength(1);
            expect(pendingDelete.value).toEqual({ id: 1 });
        });

        it("confirmDelete is a no-op when no note is pending", async () => {
            const { confirmDelete } = usePostItNotesPage(props);
            await Promise.resolve();

            await confirmDelete();

            expect(apiMethods.delete).not.toHaveBeenCalled();
        });
    });

    describe("search / filteredNotes", () => {
        const fixture = [
            {
                id: 1,
                title: "Shopping",
                content: "milk and eggs",
                color: "#FFEB3B",
            },
            {
                id: 2,
                title: "Ideas",
                content: "post-it brainstorm",
                color: "#90CAF9",
            },
            { id: 3, title: "TODO", content: "fix the bug", color: "#A5D6A7" },
            { id: 4, title: null, content: "rent reminder", color: "#FFCC80" },
        ];

        async function setup() {
            apiMethods.list.mockResolvedValue(okResponse({ notes: fixture }));
            const page = usePostItNotesPage(props);
            await page.loadNotes();
            return page;
        }

        it("searchQuery defaults to empty and filters yield the full list", async () => {
            const { searchQuery, filteredNotes, isFiltering, hasNoMatches } =
                await setup();

            expect(searchQuery.value).toBe("");
            expect(filteredNotes.value).toHaveLength(4);
            expect(isFiltering.value).toBe(false);
            expect(hasNoMatches.value).toBe(false);
        });

        it("matches titles case-insensitively", async () => {
            const { searchQuery, filteredNotes } = await setup();

            searchQuery.value = "shopping";

            expect(filteredNotes.value.map((n) => n.id)).toEqual([1]);
        });

        it("matches content substrings as well as titles", async () => {
            const { searchQuery, filteredNotes } = await setup();

            // "bug" lives in the content of note 3 — not in any title.
            searchQuery.value = "bug";

            expect(filteredNotes.value.map((n) => n.id)).toEqual([3]);
        });

        it("matches against null titles without crashing", async () => {
            const { searchQuery, filteredNotes } = await setup();

            // Note 4 has no title — querying its content must still succeed.
            searchQuery.value = "rent";

            expect(filteredNotes.value.map((n) => n.id)).toEqual([4]);
        });

        it("trims surrounding whitespace before matching", async () => {
            const { searchQuery, filteredNotes, isFiltering } = await setup();

            searchQuery.value = "   ";

            // Whitespace-only query is treated as no query at all.
            expect(isFiltering.value).toBe(false);
            expect(filteredNotes.value).toHaveLength(4);
        });

        it("isFiltering flips when the query has at least one non-space char", async () => {
            const { searchQuery, isFiltering } = await setup();

            searchQuery.value = "x";

            expect(isFiltering.value).toBe(true);
        });

        it("hasNoMatches becomes true when the query rules out everything", async () => {
            const { searchQuery, hasNoMatches } = await setup();

            searchQuery.value = "definitely-not-in-any-note";

            expect(hasNoMatches.value).toBe(true);
        });

        it("hasNoMatches stays false when the underlying list is empty", async () => {
            // No notes at all → the empty-state UI takes over, not the
            // no-matches UI. The composable must keep them mutually exclusive.
            apiMethods.list.mockResolvedValue(okResponse({ notes: [] }));
            const { searchQuery, hasNoMatches, isEmpty, loadNotes } =
                usePostItNotesPage(props);
            await loadNotes();

            searchQuery.value = "anything";

            expect(hasNoMatches.value).toBe(false);
            expect(isEmpty.value).toBe(true);
        });
    });

    describe("setColor / togglePalette", () => {
        it("setColor mutates the note color, closes the palette, schedules save", async () => {
            vi.useFakeTimers();
            apiMethods.update.mockResolvedValue(okResponse({ note: {} }));
            const { palettePickerOpenFor, setColor } =
                usePostItNotesPage(props);
            await vi.advanceTimersByTimeAsync(0);

            palettePickerOpenFor.value = 1;
            const note = { id: 1, color: "#FFEB3B" };
            setColor(note, "#A5D6A7");

            expect(note.color).toBe("#A5D6A7");
            expect(palettePickerOpenFor.value).toBeNull();

            await vi.advanceTimersByTimeAsync(400);
            expect(apiMethods.update).toHaveBeenCalledOnce();
        });

        it("togglePalette opens then closes on a second call for the same note", async () => {
            const { palettePickerOpenFor, togglePalette } =
                usePostItNotesPage(props);
            await Promise.resolve();

            togglePalette({ id: 3 });
            expect(palettePickerOpenFor.value).toBe(3);

            togglePalette({ id: 3 });
            expect(palettePickerOpenFor.value).toBeNull();
        });

        it("togglePalette switches focus when invoked on another note", async () => {
            const { palettePickerOpenFor, togglePalette } =
                usePostItNotesPage(props);
            await Promise.resolve();

            togglePalette({ id: 3 });
            togglePalette({ id: 7 });

            expect(palettePickerOpenFor.value).toBe(7);
        });
    });
});
