import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';

/**
 * Drag-drop reorder of notes within the same parent. Cross-parent moves
 * are out of scope for this composable (would require an additional API
 * round-trip + cycle check); use the explicit "create child" + delete
 * workflow for now.
 *
 * The composable is intentionally narrow: it persists a sibling
 * reordering and refreshes the list on failure so the UI never drifts
 * from the server state. VueDraggable mutates the children arrays
 * in-place — the API call below treats that array as the new truth.
 */
export function useNoteDragDrop({ api, refreshList }) {
    const { t } = useI18n();
    const dragging = ref(false);

    function onStart() {
        dragging.value = true;
    }

    /**
     * Persist the new order of `siblings` (after VueDraggable has mutated
     * the array). Bails silently on noop. On failure, force a refresh
     * so the UI reverts to whatever the server says.
     */
    async function persistSiblings(siblings) {
        dragging.value = false;
        const ids = siblings.map((n) => n.id);
        if (ids.length < 2) return;

        const { ok } = await api.reorder(ids);
        if (!ok) {
            toast.error(t('notes.markdown.errors.reorder_failed'));
            await refreshList();
        }
    }

    return { dragging, onStart, persistSiblings };
}
