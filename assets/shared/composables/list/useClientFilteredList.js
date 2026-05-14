import { ref, computed } from "vue";

/**
 * Flat admin list with client-side text filter.
 *
 * Use for short collections (< few hundred items) where loading the full set
 * on mount is cheap. For paginated/server-side search, see `useListPage`.
 *
 * @template T
 * @param {T[]} initialItems         hydrated from SSR / Twig payload
 * @param {string} listPath          JSON endpoint returning `{ items: T[] }`,
 *                                   called on every `reload()`
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
        const response = await fetch(listPath, { headers: { Accept: "application/json" } });
        const json = await response.json();
        items.value = json.items ?? [];
    }

    return { items, searchInput, filteredItems, reload };
}
