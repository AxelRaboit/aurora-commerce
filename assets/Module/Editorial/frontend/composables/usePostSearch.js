import { useFrontendPaginatedSearch } from "@/shared/composables/http/useFrontendPaginatedSearch.js";

export function usePostSearch(props) {
    const { items: posts, ...rest } = useFrontendPaginatedSearch({
        initialItems: props.initialPosts,
        initialPage: props.initialPage,
        initialTotalPages: props.initialTotalPages,
        initialTotal: props.initialTotal,
        searchPath: props.searchPath,
        itemsKey: "posts",
    });

    return { posts, ...rest };
}
