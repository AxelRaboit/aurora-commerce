import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { defineComponent, h, nextTick, ref } from "vue";
import { mount } from "@vue/test-utils";

vi.mock("vue-i18n", () => ({
    useI18n: () => ({ t: (key) => key }),
}));

vi.mock("vue-sonner", () => ({
    toast: { error: vi.fn(), success: vi.fn() },
}));

// Stub the sub-composables. Their own tests cover behavior — here we
// only verify that useBlockNotesPage wires them together correctly.
const searchContentMock = vi.fn().mockResolvedValue({
    ok: true,
    payload: { ids: [] },
});

vi.mock("@notes/backend/block/composables/useBlockNotesApi.js", () => ({
    useBlockNotesApi: () => ({
        list: vi.fn().mockResolvedValue({ ok: true, payload: { notes: [] } }),
        show: vi.fn().mockResolvedValue({
            ok: true,
            payload: {
                note: { id: 1, title: "T", tags: [], blocks: [] },
            },
        }),
        create: vi.fn(),
        update: vi.fn(),
        remove: vi.fn(),
        move: vi.fn(),
        searchContent: searchContentMock,
        uploadImage: vi.fn(),
    }),
}));

vi.mock("@notes/backend/block/composables/useBlockTagsApi.js", () => ({
    useBlockTagsApi: () => ({
        loading: { value: false },
        list: vi.fn(),
        rename: vi.fn(),
        merge: vi.fn(),
        remove: vi.fn(),
    }),
}));

// We do NOT mock the editor / tag filter / drag-drop / tree — those
// are pure orchestration that we want to exercise through their real
// implementations. Their underlying api comes from the mocked stub.

const { useBlockNotesPage } = await import("./useBlockNotesPage.js");

const baseProps = {
    listPath: "/api/list",
    showPath: "/api/show/__id__",
    createPath: "/api/create",
    updatePath: "/api/update/__id__",
    deletePath: "/api/delete/__id__",
    movePath: "/api/move/__id__",
    searchPath: "/api/search",
    imageUploadPath: "/api/upload",
    tagsListPath: "/api/tags",
    tagsRenamePath: "/api/tags/rename",
    tagsMergePath: "/api/tags/merge",
    tagsDeletePath: "/api/tags/delete",
    notes: [],
    extraFields: {},
};

/**
 * matchMedia is not in jsdom. Provide a minimal stub that yields a
 * non-matching media query so `useMediaQuery` returns matches=false
 * (desktop-shaped layout).
 */
function stubMatchMedia(matches = false) {
    window.matchMedia = vi.fn().mockReturnValue({
        matches,
        media: "",
        onchange: null,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        addListener: vi.fn(),
        removeListener: vi.fn(),
        dispatchEvent: vi.fn(),
    });
}

function mountPage(props = baseProps) {
    let captured;
    const Comp = defineComponent({
        setup() {
            captured = useBlockNotesPage(props, (k) => k);
            return () => h("div");
        },
    });
    const wrapper = mount(Comp);
    return { wrapper, page: captured };
}

beforeEach(() => {
    searchContentMock.mockClear();
    vi.useFakeTimers();
    stubMatchMedia(false);
});

afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
});

describe("useBlockNotesPage", () => {
    it("exposes the editor surface and the tag filter / tree state", () => {
        const { page } = mountPage();

        // editor surface
        expect(page.notes).toBeDefined();
        expect(page.selectedId).toBeDefined();
        expect(page.form).toBeDefined();
        expect(page.saving).toBeDefined();
        expect(page.deleting).toBeDefined();
        expect(page.saveStatus).toBeDefined();

        // tag filter surface
        expect(page.availableTags).toBeDefined();
        expect(page.selectedTags).toBeDefined();
        expect(typeof page.toggleTag).toBe("function");
        expect(typeof page.clearTags).toBe("function");

        // tree
        expect(page.tree).toBeDefined();
        expect(page.treeQuery).toBeDefined();

        // drag-drop spread
        expect(typeof page.onDragStart).toBe("function");
        expect(typeof page.onDropOnNote).toBe("function");
        expect(typeof page.onDropOnRoot).toBe("function");
    });

    it("starts with the sidebar open on desktop and closed on mobile", () => {
        stubMatchMedia(true); // mobile
        const { page } = mountPage();
        expect(page.isMobile.value).toBe(true);
        expect(page.sidebarOpen.value).toBe(false);
    });

    it("toggles the sidebar on isMobile changes", async () => {
        const { page } = mountPage();

        expect(page.sidebarOpen.value).toBe(true);
        page.isMobile.value = true;
        await nextTick();
        expect(page.sidebarOpen.value).toBe(false);

        page.isMobile.value = false;
        await nextTick();
        expect(page.sidebarOpen.value).toBe(true);
    });

    it("dragEnabled is false while a tree query is active", async () => {
        const { page } = mountPage();

        expect(page.dragEnabled.value).toBe(true);

        page.treeQuery.value = "foo";
        await nextTick();
        expect(page.dragEnabled.value).toBe(false);

        page.treeQuery.value = "";
        await nextTick();
        expect(page.dragEnabled.value).toBe(true);
    });

    it("dragEnabled is false while any tag is selected", async () => {
        const { page } = mountPage();

        page.toggleTag("foo");
        await nextTick();
        expect(page.dragEnabled.value).toBe(false);

        page.clearTags();
        await nextTick();
        expect(page.dragEnabled.value).toBe(true);
    });

    it("debounces the content search and only fires once", async () => {
        const { page } = mountPage();

        page.treeQuery.value = "hello";
        await nextTick();
        expect(page.contentSearchLoading.value).toBe(true);
        expect(searchContentMock).not.toHaveBeenCalled();

        // Reset before the debounce fires -> never reaches search.
        page.treeQuery.value = "h";
        await nextTick();

        vi.advanceTimersByTime(150);
        expect(searchContentMock).not.toHaveBeenCalled();

        vi.advanceTimersByTime(200);
        await Promise.resolve();
        await Promise.resolve();

        expect(searchContentMock).toHaveBeenCalledTimes(1);
        expect(searchContentMock).toHaveBeenLastCalledWith("h");
    });

    it("clears content search state when the query is emptied", async () => {
        const { page } = mountPage();

        page.treeQuery.value = "hello";
        await nextTick();
        page.treeQuery.value = "";
        await nextTick();

        expect(page.contentSearchLoading.value).toBe(false);
    });

    it("treats whitespace-only queries like empty (no search fired)", async () => {
        const { page } = mountPage();

        page.treeQuery.value = "   ";
        await nextTick();

        vi.advanceTimersByTime(500);
        await Promise.resolve();

        expect(searchContentMock).not.toHaveBeenCalled();
        expect(page.contentSearchLoading.value).toBe(false);
    });

    it("tagManagerOpen and treeQuery default to closed/empty", () => {
        const { page } = mountPage();
        expect(page.tagManagerOpen.value).toBe(false);
        expect(page.treeQuery.value).toBe("");
    });

    it("exposes the saveStatusDisplay computed", () => {
        const { page } = mountPage();
        expect(page.saveStatusDisplay).toBeDefined();
        // status display ref returns at least a defined value
        expect(page.saveStatusDisplay.value).toBeDefined();
    });
});
