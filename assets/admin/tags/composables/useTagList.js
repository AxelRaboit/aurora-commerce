import { useList } from "@/composables/useList.js";

export function useTagList(tagsPath, initialTags, initialSearch) {
    const {
        items: tags,
        addItem: addTag,
        updateItem: updateTag,
        removeItem: removeTag,
        ...rest
    } = useList(tagsPath, initialTags, initialSearch);

    return { tags, addTag, updateTag, removeTag, ...rest };
}
