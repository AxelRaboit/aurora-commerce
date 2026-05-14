import { ref } from "vue";
import { useFrontendRequest } from "@/shared/composables/http/useFrontendRequest.js";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useShopSearch(props) {
    const listings = ref(props.listings);
    const page = ref(props.pagination.page);
    const totalPages = ref(props.pagination.totalPages);
    const query = ref("");

    const { loading, request } = useFrontendRequest();

    async function fetchPage(q, p) {
        const params = new URLSearchParams({ page: p });
        if (q.trim()) params.set("q", q.trim());

        const data = await request(
            `${props.searchPath}?${params}`,
            null,
            HttpMethod.Get,
        );
        if (!data?.success) return;

        listings.value = data.listings;
        page.value = data.pagination.page;
        totalPages.value = data.pagination.totalPages;
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

    return { query, listings, page, totalPages, loading, onSearch, goToPage };
}
