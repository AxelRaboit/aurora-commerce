import { computed } from 'vue';

/**
 * Build a hierarchical tree from a flat list of notes (sorted by position).
 * Each node looks like { id, parentId, title, ..., children: [...] }.
 *
 * Notes whose parent is missing (deleted before flush) bubble up to root.
 *
 * If `queryRef` is given, the tree is filtered to keep only nodes whose
 * title matches the query (case-insensitive substring), plus any ancestor
 * needed to keep the surviving leaves attached to the root. A `matched`
 * flag tells the UI which rows are direct hits vs. just structural carriers.
 */
export function useNoteTree(notesRef, queryRef = null) {
    const tree = computed(() => {
        const query = (queryRef?.value ?? '').trim().toLowerCase();
        const fullTree = buildTree(notesRef.value);

        if (query === '') {
            // mark every node as matched so callers can apply the same styling logic
            return markAllMatched(fullTree);
        }

        return filterTree(fullTree, query);
    });

    function buildTree(flat) {
        const byId = new Map();
        const roots = [];

        for (const note of flat) {
            byId.set(note.id, { ...note, children: [], matched: false });
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

    function markAllMatched(nodes) {
        return nodes.map((n) => ({ ...n, matched: true, children: markAllMatched(n.children) }));
    }

    /**
     * Keep a node when it matches itself or has any descendant that does.
     * Walking depth-first lets us prune cleanly without a second pass.
     */
    function filterTree(nodes, query) {
        const kept = [];
        for (const node of nodes) {
            const children = filterTree(node.children, query);
            const selfMatch = (node.title ?? '').toLowerCase().includes(query);
            if (selfMatch || children.length > 0) {
                kept.push({ ...node, matched: selfMatch, children });
            }
        }
        return kept;
    }

    return { tree };
}
