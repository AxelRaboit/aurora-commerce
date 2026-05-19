import { ref, computed } from "vue";

/**
 * Aggregate unique tags from the flat block-notes list and expose a
 * toggleable selection. Mirror of the Markdown filter — kept separate
 * so each module owns its tag vocabulary.
 */
export function useBlockNoteTagFilter(notesRef) {
    const selectedTags = ref([]);

    const availableTags = computed(() => {
        const set = new Set();
        for (const note of notesRef.value ?? []) {
            for (const tag of note.tags ?? []) {
                if (typeof tag === "string" && tag.trim() !== "") {
                    set.add(tag);
                }
            }
        }
        return Array.from(set).sort((a, b) =>
            a.localeCompare(b, undefined, { sensitivity: "base" }),
        );
    });

    function toggleTag(tag) {
        const index = selectedTags.value.indexOf(tag);
        if (index === -1) {
            selectedTags.value = [...selectedTags.value, tag];
        } else {
            selectedTags.value = selectedTags.value.filter((t) => t !== tag);
        }
    }

    function clearTags() {
        selectedTags.value = [];
    }

    function pruneMissingTags() {
        if (selectedTags.value.length === 0) return;
        const existing = new Set(
            (notesRef.value ?? []).flatMap((n) => n.tags ?? []),
        );
        selectedTags.value = selectedTags.value.filter((tag) =>
            existing.has(tag),
        );
    }

    return {
        availableTags,
        selectedTags,
        toggleTag,
        clearTags,
        pruneMissingTags,
    };
}
