import { ref, computed } from "vue";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

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

    const { loading, request } = useRequest();

    const hasMore = computed(() => page.value < totalPages.value);

    async function loadMore() {
        if (loading.value || !hasMore.value) return;
        const params = new URLSearchParams({
            page: String(page.value + 1),
        });
        for (const [key, value] of Object.entries(getExtraParams())) {
            if (value !== undefined && value !== null && value !== "") {
                params.set(key, String(value));
            }
        }
        const data = await request(`${path}?${params}`, null, HttpMethod.Get);
        if (data?.success) {
            items.value.push(...(data.items ?? []));
            page.value = data.page ?? page.value + 1;
            totalPages.value = data.totalPages ?? totalPages.value;
        }
    }

    return { items, page, totalPages, hasMore, loading, loadMore };
}
