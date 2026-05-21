import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, nextTick } from "vue";

vi.mock("vue-i18n", () => ({
    useI18n: () => ({ t: (key) => key }),
}));

vi.mock("vue-sonner", () => ({
    toast: { success: vi.fn(), error: vi.fn() },
}));

const { useBlockNoteTagManager } = await import("./useBlockNoteTagManager.js");

function makeApi(tags = []) {
    return {
        list: vi.fn().mockResolvedValue({ success: true, tags }),
        rename: vi.fn().mockResolvedValue({ success: true, affected: 2 }),
        merge: vi.fn().mockResolvedValue({ success: true, affected: 3 }),
        remove: vi.fn().mockResolvedValue({ success: true, affected: 1 }),
    };
}

describe("useBlockNoteTagManager", () => {
    let api;
    let show;
    let onChanged;

    beforeEach(() => {
        api = makeApi([
            { tag: "alpha", count: 2 },
            { tag: "beta", count: 1 },
        ]);
        show = ref(false);
        onChanged = vi.fn();
    });

    it("loads tags when the modal opens", async () => {
        const { tags } = useBlockNoteTagManager({ api, show, onChanged });

        show.value = true;
        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        expect(api.list).toHaveBeenCalledTimes(1);
        expect(tags.value).toHaveLength(2);
    });

    it("does not load tags when show stays false", async () => {
        useBlockNoteTagManager({ api, show, onChanged });

        await nextTick();
        await Promise.resolve();

        expect(api.list).not.toHaveBeenCalled();
    });

    it("clears tags when the api returns success: false", async () => {
        api.list.mockResolvedValueOnce({ success: false });
        const { tags } = useBlockNoteTagManager({ api, show, onChanged });

        show.value = true;
        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        expect(tags.value).toEqual([]);
    });

    it("filters by query case-insensitively", async () => {
        const { query, filteredTags } = useBlockNoteTagManager({
            api,
            show,
            onChanged,
        });

        show.value = true;
        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        query.value = "ALPH";
        expect(filteredTags.value.map((e) => e.tag)).toEqual(["alpha"]);
    });

    it("returns all tags when the query is empty or whitespace", async () => {
        const { query, filteredTags } = useBlockNoteTagManager({
            api,
            show,
            onChanged,
        });

        show.value = true;
        await nextTick();
        await Promise.resolve();
        await Promise.resolve();

        query.value = "   ";
        expect(filteredTags.value).toHaveLength(2);
    });

    it("toggles selection and exposes selectedTags as a list", () => {
        const { toggleSelected, isSelected, selectedTags } =
            useBlockNoteTagManager({
                api,
                show,
                onChanged,
            });

        toggleSelected("alpha");
        toggleSelected("beta");
        expect(isSelected("alpha")).toBe(true);
        expect(selectedTags.value).toEqual(["alpha", "beta"]);

        toggleSelected("alpha");
        expect(isSelected("alpha")).toBe(false);
        expect(selectedTags.value).toEqual(["beta"]);
    });

    it("begins, drafts, then confirms a rename", async () => {
        const { beginRename, renaming, confirmRename } = useBlockNoteTagManager(
            {
                api,
                show,
                onChanged,
            },
        );

        beginRename({ tag: "alpha", count: 2 });
        expect(renaming.value).toEqual({ source: "alpha", draft: "alpha" });

        renaming.value.draft = "ALPHA";
        await confirmRename();

        expect(api.rename).toHaveBeenCalledWith("alpha", "ALPHA");
        expect(onChanged).toHaveBeenCalled();
        expect(renaming.value).toBeNull();
    });

    it("skips rename when draft is empty or unchanged", async () => {
        const { beginRename, renaming, confirmRename } = useBlockNoteTagManager(
            {
                api,
                show,
                onChanged,
            },
        );

        beginRename({ tag: "alpha", count: 2 });
        renaming.value.draft = "alpha"; // unchanged
        await confirmRename();
        expect(api.rename).not.toHaveBeenCalled();

        beginRename({ tag: "alpha", count: 2 });
        renaming.value.draft = "   ";
        await confirmRename();
        expect(api.rename).not.toHaveBeenCalled();
    });

    it("does not fire onChanged when api.rename fails", async () => {
        api.rename.mockResolvedValueOnce({ success: false });
        const { beginRename, renaming, confirmRename } = useBlockNoteTagManager(
            {
                api,
                show,
                onChanged,
            },
        );

        beginRename({ tag: "alpha", count: 2 });
        renaming.value.draft = "ALPHA";
        await confirmRename();

        expect(api.rename).toHaveBeenCalledWith("alpha", "ALPHA");
        expect(onChanged).not.toHaveBeenCalled();
    });

    it("begins then confirms a delete and drops the tag from selection", async () => {
        const {
            toggleSelected,
            beginDelete,
            confirmDelete,
            pendingDelete,
            selectedTags,
        } = useBlockNoteTagManager({ api, show, onChanged });

        toggleSelected("alpha");
        beginDelete({ tag: "alpha", count: 2 });
        expect(pendingDelete.value).toBe("alpha");

        await confirmDelete();

        expect(api.remove).toHaveBeenCalledWith("alpha");
        expect(onChanged).toHaveBeenCalled();
        expect(pendingDelete.value).toBeNull();
        expect(selectedTags.value).toEqual([]);
    });

    it("cancelDelete and cancelRename clear pending state", () => {
        const {
            beginDelete,
            cancelDelete,
            pendingDelete,
            beginRename,
            cancelRename,
            renaming,
        } = useBlockNoteTagManager({ api, show, onChanged });

        beginDelete({ tag: "alpha", count: 2 });
        cancelDelete();
        expect(pendingDelete.value).toBeNull();

        beginRename({ tag: "alpha", count: 2 });
        cancelRename();
        expect(renaming.value).toBeNull();
    });

    it("merges only when >= 2 sources and a non-empty target", async () => {
        const { toggleSelected, mergeTarget, confirmMerge } =
            useBlockNoteTagManager({ api, show, onChanged });

        // One source -> no-op
        toggleSelected("alpha");
        mergeTarget.value = "done";
        await confirmMerge();
        expect(api.merge).not.toHaveBeenCalled();

        // Two sources + target -> fires
        toggleSelected("beta");
        await confirmMerge();
        expect(api.merge).toHaveBeenCalledWith(["alpha", "beta"], "done");
        expect(onChanged).toHaveBeenCalled();
    });

    it("skips merge when target is whitespace only", async () => {
        const { toggleSelected, mergeTarget, confirmMerge } =
            useBlockNoteTagManager({ api, show, onChanged });

        toggleSelected("alpha");
        toggleSelected("beta");
        mergeTarget.value = "   ";
        await confirmMerge();

        expect(api.merge).not.toHaveBeenCalled();
    });
});
