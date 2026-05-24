import { ref } from "vue";
import { useRequest } from "@/shared/composables/http/frontend/useRequest.js";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Generic paginated search composable for public frontend pages.
 *
 * @param {object} opts
 * @param {Array}  opts.initialItems       - First-page items from SSR props
 * @param {number} opts.initialPage
 * @param {number} opts.initialTotalPages
 * @param {number} opts.initialTotal
 * @param {string} opts.searchPath         - API endpoint URL
 * @param {string} opts.itemsKey           - Key in API response that holds the items array (e.g. 'posts', 'listings', 'items')
 */
export function usePaginatedSearch({
    initialItems,
    initialPage,
    initialTotalPages,
    initialTotal,
    searchPath,
    itemsKey,
}) {
    const items = ref(initialItems);
    const page = ref(initialPage);
    const totalPages = ref(initialTotalPages);
    const total = ref(initialTotal);
    const query = ref("");

    const { loading, request } = useRequest();

    async function fetchPage(q, p) {
        const params = new URLSearchParams({ page: p });
        if (q.trim()) params.set("q", q.trim());

        const data = await request(
            `${searchPath}?${params}`,
            null,
            HttpMethod.Get,
        );
        if (!data?.success) return;

        items.value = data[itemsKey];
        page.value = data.page;
        totalPages.value = data.totalPages;
        total.value = data.total;
    }

    const debouncedSearch = useDebounce((q) => fetchPage(q, 1), 300);

    function onSearch(q) {
        query.value = q;
        debouncedSearch(q);
    }

    function goToPage(p) {
        page.value = p;
        fetchPage(query.value, p);
    }

    return {
        items,
        query,
        page,
        totalPages,
        total,
        loading,
        onSearch,
        goToPage,
    };
}
