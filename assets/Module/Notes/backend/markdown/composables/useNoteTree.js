import { computed } from 'vue';

/**
 * Build a hierarchical tree from a flat list of notes (sorted by position).
 * Each node looks like { id, parentId, title, ..., children: [...] }.
 *
 * Notes whose parent is missing (deleted before flush) bubble up to root.
 */
export function useNoteTree(notesRef) {
    const tree = computed(() => buildTree(notesRef.value));

    function buildTree(flat) {
        const byId = new Map();
        const roots = [];

        for (const note of flat) {
            byId.set(note.id, { ...note, children: [] });
        }

        for (const node of byId.values()) {
            const parent = node.parentId != null ? byId.get(node.parentId) : null;
            if (parent) {
                parent.children.push(node);
            } else {
                roots.push(node);
            }
        }

        const byPosition = (a, b) => (a.position ?? 0) - (b.position ?? 0);
        roots.sort(byPosition);
        for (const node of byId.values()) {
            node.children.sort(byPosition);
        }

        return roots;
    }

    return { tree };
}
