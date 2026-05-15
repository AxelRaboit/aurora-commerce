import { ref, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import { flattenTreeForReorder } from '@/shared/composables/tree/useHierarchicalTree.js';

const BODY_DRAGGING_CLASS = 'notes-dragging';

/**
 * Drag-drop hierarchy editing for the note tree.
 *
 * Follows the existing Aurora pattern (taxonomies, listing categories):
 * all VueDraggable instances share a single group name so cross-parent
 * drag works; on drag end we flatten the locally-mutated tree and POST
 * the whole structure in one shot. The backend `reorder` endpoint
 * applies all parent + position changes atomically and detects cycles
 * up-front.
 *
 * Also toggles a `body.notes-dragging` class so empty children
 * containers can expand into easy-to-hit drop zones via CSS only,
 * without per-component reactive plumbing.
 */
export function useNoteDragDrop({ tree, api, refreshList }) {
    const { t } = useI18n();
    const dragging = ref(false);

    function onStart() {
        dragging.value = true;
        document.body.classList.add(BODY_DRAGGING_CLASS);
    }

    function teardown() {
        dragging.value = false;
        document.body.classList.remove(BODY_DRAGGING_CLASS);
    }

    /**
     * Persist the full intended tree state. Wraps in nextTick so that
     * VueDraggable has time to settle the mutations before we serialize.
     */
    async function onEnd() {
        await nextTick();
        teardown();

        const entries = flattenTreeForReorder(tree.value);
        if (entries.length === 0) return;

        const { ok, payload } = await api.reorder(entries);
        if (!ok) {
            const msg = payload?.error === 'cycle'
                ? t('notes.markdown.errors.reorder_cycle')
                : t('notes.markdown.errors.reorder_failed');
            toast.error(msg);
        }
        // Always refresh from server — on success to pick up server-side
        // position normalization, on failure to revert the optimistic mutation.
        await refreshList();
    }

    return { dragging, onStart, onEnd };
}
