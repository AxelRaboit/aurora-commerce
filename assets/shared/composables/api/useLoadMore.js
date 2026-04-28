import { ref, computed } from "vue";

/**
 * Generic "load more" composable for paginated XHR endpoints.
 *
 * The endpoint must return JSON of shape:
 *   { ok: true, items: [...], page: number, totalPages: number }
 *
 * @param {string} path - Endpoint URL (will receive ?page=N)
 * @param {object} initial - Initial paginated payload (server-rendered first page)
 * @param {(extra?: object) => object} getExtraParams - Optional extra query params
 */
export function useLoadMore(
    path,
    initial = { items: [], page: 1, totalPages: 1 },
    getExtraParams = () => ({}),
) {
    const items = ref([...(initial.items ?? [])]);
    const page = ref(initial.page ?? 1);
    const totalPages = ref(initial.totalPages ?? 1);
    const loading = ref(false);

    const hasMore = computed(() => page.value < totalPages.value);

    async function loadMore() {
        if (loading.value || !hasMore.value) return;
        loading.value = true;
        try {
            const params = new URLSearchParams({
                page: String(page.value + 1),
            });
            for (const [key, value] of Object.entries(getExtraParams())) {
                if (value !== undefined && value !== null && value !== "") {
                    params.set(key, String(value));
                }
            }
            const response = await fetch(`${path}?${params}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            const data = await response.json();
            if (data.ok) {
                items.value.push(...(data.items ?? []));
                page.value = data.page ?? page.value + 1;
                totalPages.value = data.totalPages ?? totalPages.value;
            }
        } finally {
            loading.value = false;
        }
    }

    return { items, page, totalPages, hasMore, loading, loadMore };
}
