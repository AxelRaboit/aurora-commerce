import { usePaginatedSearch } from "@/shared/composables/http/frontend/usePaginatedSearch.js";

export function useShopSearch(props) {
    const { items: listings, ...rest } = usePaginatedSearch({
        initialItems: props.listings,
        initialPage: props.initialPage,
        initialTotalPages: props.initialTotalPages,
        initialTotal: props.initialTotal,
        searchPath: props.searchPath,
        itemsKey: "listings",
    });

    return { listings, ...rest };
}
