import { ref } from "vue";

/**
 * Persisted sort state: a `sortBy` field + `sortDir` (asc/desc). Clicking
 * the same field toggles direction; clicking a new one resets to asc.
 *
 * Both values live in localStorage so the user's sort choice survives
 * reloads. Pass two distinct keys per consumer to avoid collisions
 * between lists.
 *
 * @param {string} fieldKey      localStorage key for sortBy
 * @param {string} dirKey        localStorage key for sortDir
 * @param {string} [defaultField="name"]
 * @param {"asc"|"desc"} [defaultDir="asc"]
 */
export function useListSort(
    fieldKey,
    dirKey,
    defaultField = "name",
    defaultDir = "asc",
) {
    const sortBy = ref(localStorage.getItem(fieldKey) ?? defaultField);
    const sortDir = ref(localStorage.getItem(dirKey) ?? defaultDir);

    function setSort(field) {
        if (sortBy.value === field) {
            sortDir.value = sortDir.value === "asc" ? "desc" : "asc";
        } else {
            sortBy.value = field;
            sortDir.value = "asc";
        }
        localStorage.setItem(fieldKey, sortBy.value);
        localStorage.setItem(dirKey, sortDir.value);
    }

    return { sortBy, sortDir, setSort };
}
