import { usePaginatedSearch } from "@/shared/composables/http/frontend/usePaginatedSearch.js";

export function useDocumentSearch(props) {
    const { items, ...rest } = usePaginatedSearch({
        initialItems: props.initialItems,
        initialPage: props.initialPage,
        initialTotalPages: props.initialTotalPages,
        initialTotal: props.initialTotal,
        searchPath: props.searchPath,
        itemsKey: "items",
    });

    return { items, ...rest };
}
