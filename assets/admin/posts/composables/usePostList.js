import { useList } from "@/composables/useList.js";

export function usePostList(postsPath, initialPosts, initialSearch) {
    const {
        items: posts,
        addItem: addPost,
        updateItem: updatePost,
        removeItem: removePost,
        ...rest
    } = useList(postsPath, initialPosts, initialSearch);

    return { posts, addPost, updatePost, removePost, ...rest };
}
