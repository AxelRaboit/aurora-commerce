import { useFrontendPaginatedSearch } from "@/shared/composables/http/useFrontendPaginatedSearch.js";

export function useShopSearch(props) {
    const { items: listings, ...rest } = useFrontendPaginatedSearch({
        initialItems: props.listings,
        initialPage: props.initialPage,
        initialTotalPages: props.initialTotalPages,
        initialTotal: props.initialTotal,
        searchPath: props.searchPath,
        itemsKey: "listings",
    });

    return { listings, ...rest };
}
