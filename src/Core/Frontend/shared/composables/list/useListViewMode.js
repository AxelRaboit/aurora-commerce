import { ref } from "vue";

/**
 * Persisted list view toggle (e.g. "grid" ↔ "list"). Reads + writes the
 * preference under a caller-supplied localStorage key so each list page
 * keeps its own remembered choice across reloads.
 *
 * @param {string} storageKey  e.g. "aurora-ged-view"
 * @param {string} [defaultMode="grid"]  initial value if nothing is stored
 */
export function useListViewMode(storageKey, defaultMode = "grid") {
    const viewMode = ref(localStorage.getItem(storageKey) ?? defaultMode);

    function setViewMode(mode) {
        viewMode.value = mode;
        localStorage.setItem(storageKey, mode);
    }

    return { viewMode, setViewMode };
}
