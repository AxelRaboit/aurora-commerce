import { ref } from "vue";
import { useFrontendRequest } from "@/shared/composables/http/useFrontendRequest.js";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useDocumentSearch(props) {
    const items = ref(props.initialItems);
    const page = ref(props.initialPage);
    const totalPages = ref(props.initialTotalPages);
    const total = ref(props.initialTotal);
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

        items.value = data.items;
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
        query,
        items,
        page,
        totalPages,
        total,
        loading,
        onSearch,
        goToPage,
    };
}
