import { ref, computed } from "vue";

/**
 * Flat admin list with client-side text filter.
 *
 * Use for short collections (< few hundred items) where loading the full set
 * on mount is cheap. For paginated/server-side search, see `useListPage`.
 *
 * `listPath` is optional. When provided, `reload()` refetches from it.
 * When absent (consumer manages `items` externally, e.g. through form-action
 * response payloads), `reload()` is a no-op.
 *
 * @template T
 * @param {T[]} initialItems         hydrated from SSR / Twig payload
 * @param {string|null} listPath     JSON endpoint returning `{ items: T[] }`,
 *                                   or null when items are updated externally
 * @param {(item: T, lowerQuery: string) => boolean} matcher  filter predicate
 * @returns {{
 *   items: import('vue').Ref<T[]>,
 *   searchInput: import('vue').Ref<string>,
 *   filteredItems: import('vue').ComputedRef<T[]>,
 *   reload: () => Promise<void>,
 * }}
 */
export function useClientFilteredList(initialItems, listPath, matcher) {
    const items = ref([...(initialItems ?? [])]);
    const searchInput = ref("");

    const filteredItems = computed(() => {
        const query = searchInput.value.toLowerCase().trim();
        if (!query) return items.value;
        return items.value.filter((item) => matcher(item, query));
    });

    async function reload() {
        if (!listPath) return;
        const response = await fetch(listPath, {
            headers: { Accept: "application/json" },
        });
        const json = await response.json();
        items.value = json.items ?? [];
    }

    return { items, searchInput, filteredItems, reload };
}
