import { describe, it, expect } from "vitest";
import { ref } from "vue";
import { useBlockNoteTagFilter } from "./useBlockNoteTagFilter.js";

const notes = [
    { id: 1, title: "A", tags: ["work", "urgent"] },
    { id: 2, title: "B", tags: ["personal"] },
    { id: 3, title: "C", tags: ["work"] },
    { id: 4, title: "D", tags: [] },
    { id: 5, title: "E" }, // missing tags
];

describe("useBlockNoteTagFilter", () => {
    it("aggregates unique tags sorted case-insensitively", () => {
        const notesRef = ref(notes);
        const { availableTags } = useBlockNoteTagFilter(notesRef);

        expect(availableTags.value).toEqual(["personal", "urgent", "work"]);
    });

    it("ignores empty and whitespace-only tags", () => {
        const notesRef = ref([{ id: 1, tags: ["a", "", "   ", "b"] }]);
        const { availableTags } = useBlockNoteTagFilter(notesRef);

        expect(availableTags.value).toEqual(["a", "b"]);
    });

    it("ignores non-string tag entries gracefully", () => {
        const notesRef = ref([
            { id: 1, tags: ["a", null, undefined, 42, "b"] },
        ]);
        const { availableTags } = useBlockNoteTagFilter(notesRef);

        expect(availableTags.value).toEqual(["a", "b"]);
    });

    it("toggles a tag in and out of the selection", () => {
        const notesRef = ref(notes);
        const { selectedTags, toggleTag } = useBlockNoteTagFilter(notesRef);

        toggleTag("work");
        expect(selectedTags.value).toEqual(["work"]);

        toggleTag("urgent");
        expect(selectedTags.value).toEqual(["work", "urgent"]);

        toggleTag("work");
        expect(selectedTags.value).toEqual(["urgent"]);
    });

    it("clears the entire selection", () => {
        const notesRef = ref(notes);
        const { selectedTags, toggleTag, clearTags } =
            useBlockNoteTagFilter(notesRef);

        toggleTag("work");
        toggleTag("urgent");
        clearTags();

        expect(selectedTags.value).toEqual([]);
    });

    it("recomputes available tags when the notes list changes", () => {
        const notesRef = ref([{ id: 1, tags: ["a"] }]);
        const { availableTags } = useBlockNoteTagFilter(notesRef);

        expect(availableTags.value).toEqual(["a"]);

        notesRef.value = [{ id: 2, tags: ["b", "c"] }];
        expect(availableTags.value).toEqual(["b", "c"]);
    });

    it("prunes selected tags that no longer exist anywhere", () => {
        const notesRef = ref(notes);
        const { selectedTags, toggleTag, pruneMissingTags } =
            useBlockNoteTagFilter(notesRef);

        toggleTag("work");
        toggleTag("ghost");
        expect(selectedTags.value).toEqual(["work", "ghost"]);

        pruneMissingTags();
        expect(selectedTags.value).toEqual(["work"]);
    });

    it("pruneMissingTags is a no-op when nothing is selected", () => {
        const notesRef = ref(notes);
        const { selectedTags, pruneMissingTags } =
            useBlockNoteTagFilter(notesRef);

        pruneMissingTags();
        expect(selectedTags.value).toEqual([]);
    });

    it("handles a null/undefined notes ref value without throwing", () => {
        const notesRef = ref(null);
        const { availableTags, pruneMissingTags, selectedTags, toggleTag } =
            useBlockNoteTagFilter(notesRef);

        expect(availableTags.value).toEqual([]);

        toggleTag("a");
        pruneMissingTags();
        expect(selectedTags.value).toEqual([]);
    });
});
