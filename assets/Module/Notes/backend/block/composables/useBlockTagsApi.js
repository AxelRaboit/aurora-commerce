import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * HTTP layer for the global tag-management endpoints of the Block-notes
 * module. Mirror of `useMarkdownTagsApi` — kept independent so each
 * notes flavour owns its own vocabulary.
 */
export function useBlockTagsApi(props) {
    const { loading, request } = useRequest();

    return {
        loading,
        list: () => request(props.tagsListPath, null, "GET"),
        rename: (oldTag, newTag) =>
            request(props.tagsRenamePath, { oldTag, newTag }),
        merge: (sourceTags, targetTag) =>
            request(props.tagsMergePath, { sourceTags, targetTag }),
        remove: (tag) => request(props.tagsDeletePath, { tag }),
    };
}
