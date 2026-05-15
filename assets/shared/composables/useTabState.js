import { ref, computed } from "vue";

/**
 * Tracks the active tab key with optional persistence in `localStorage`.
 * Returns the active ref + a `select(key)` helper that also persists.
 *
 * @param {string[]}            validKeys Allowed tab keys (used to discard stale storage values).
 * @param {object}              options
 * @param {string|null}         [options.storageKey] When set, persists the active tab under this key.
 * @param {string|null}         [options.defaultKey] Falls back to the first valid key when omitted.
 */
export function useTabState(
    validKeys,
    { storageKey = null, defaultKey = null } = {},
) {
    const fallback =
        defaultKey && validKeys.includes(defaultKey)
            ? defaultKey
            : validKeys[0];

    const initial = (() => {
        if (!storageKey) return fallback;
        try {
            const saved = localStorage.getItem(storageKey);
            if (saved && validKeys.includes(saved)) return saved;
        } catch (_) {
            /* ignored — private mode, full storage, etc. */
        }
        return fallback;
    })();

    const activeTab = ref(initial);

    function select(key) {
        if (!validKeys.includes(key)) return;
        activeTab.value = key;
        if (!storageKey) return;
        try {
            localStorage.setItem(storageKey, key);
        } catch (_) {
            /* ignored */
        }
    }

    function isActive(key) {
        return activeTab.value === key;
    }

    return { activeTab, select, isActive };
}
