import { ref } from "vue";

export function useMultiSelection() {
    const selectedIds = ref(new Set());
    const isSelecting = ref(false);

    function toggle(id) {
        const next = new Set(selectedIds.value);
        if (next.has(id)) next.delete(id);
        else next.add(id);
        selectedIds.value = next;
    }

    function selectAll(ids) {
        selectedIds.value = new Set(ids);
    }

    function clear() {
        selectedIds.value = new Set();
        isSelecting.value = false;
    }

    return { selectedIds, isSelecting, toggle, selectAll, clear };
}
