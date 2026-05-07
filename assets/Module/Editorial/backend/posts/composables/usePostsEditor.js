import { ref } from "vue";

export function usePostsEditor(addPost, updatePost) {
    const view = ref("list");
    const editingPostId = ref(null);

    function openCreate() {
        editingPostId.value = null;
        view.value = "editor";
    }
    function openEdit(post) {
        editingPostId.value = post.id;
        view.value = "editor";
    }
    function closeEditor() {
        view.value = "list";
    }

    function onEditorSaved(post, isNew) {
        if (isNew) {
            addPost(post);
            editingPostId.value = post.id;
        } else updatePost(post);
    }

    return {
        view,
        editingPostId,
        openCreate,
        openEdit,
        closeEditor,
        onEditorSaved,
    };
}
