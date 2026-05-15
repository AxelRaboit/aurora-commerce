import { describe, it, expect } from 'vitest';
import { ref } from 'vue';
import { useNoteTree } from './useNoteTree.js';

const flatNotes = [
    { id: 1, parentId: null, title: 'Welcome', position: 0 },
    { id: 2, parentId: 1, title: 'Getting Started', position: 0 },
    { id: 3, parentId: 1, title: 'Tips', position: 1 },
    { id: 4, parentId: null, title: 'Tasks', position: 1 },
    { id: 5, parentId: 4, title: 'Errands', position: 0 },
];

describe('useNoteTree', () => {
    it('builds a hierarchical tree from a flat list', () => {
        const notes = ref(flatNotes);
        const { tree } = useNoteTree(notes);

        expect(tree.value).toHaveLength(2);
        expect(tree.value[0].id).toBe(1);
        expect(tree.value[0].children).toHaveLength(2);
        expect(tree.value[0].children.map((c) => c.id)).toEqual([2, 3]);
        expect(tree.value[1].id).toBe(4);
        expect(tree.value[1].children).toHaveLength(1);
    });

    it('marks every node as matched when no query is given', () => {
        const notes = ref(flatNotes);
        const { tree } = useNoteTree(notes);

        expect(tree.value[0].matched).toBe(true);
        expect(tree.value[0].children[0].matched).toBe(true);
    });

    it('filters nodes by case-insensitive title substring', () => {
        const notes = ref(flatNotes);
        const query = ref('task');
        const { tree } = useNoteTree(notes, query);

        expect(tree.value).toHaveLength(1);
        expect(tree.value[0].id).toBe(4);
        expect(tree.value[0].matched).toBe(true);
    });

    it('keeps ancestors of matching descendants as unmatched carriers', () => {
        const notes = ref(flatNotes);
        const query = ref('errands');
        const { tree } = useNoteTree(notes, query);

        expect(tree.value).toHaveLength(1);
        // Tasks is the ancestor — it should appear but NOT be flagged as matched
        expect(tree.value[0].id).toBe(4);
        expect(tree.value[0].matched).toBe(false);
        // Errands is the actual hit
        expect(tree.value[0].children[0].id).toBe(5);
        expect(tree.value[0].children[0].matched).toBe(true);
    });

    it('returns an empty tree when nothing matches', () => {
        const notes = ref(flatNotes);
        const query = ref('xyzzy');
        const { tree } = useNoteTree(notes, query);

        expect(tree.value).toHaveLength(0);
    });

    it('treats whitespace-only queries as no-query', () => {
        const notes = ref(flatNotes);
        const query = ref('   ');
        const { tree } = useNoteTree(notes, query);

        expect(tree.value).toHaveLength(2);
    });
});
