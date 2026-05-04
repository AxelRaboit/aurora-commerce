import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function useGalleryEditComments(commentDeletePath, comments) {
    const { t } = useI18n();

    const pendingCommentDelete = ref(null);
    const commentDeleteLoading = ref(false);

    function askDeleteComment(comment) {
        if (!commentDeletePath) return;
        pendingCommentDelete.value = comment;
    }

    async function confirmDeleteComment() {
        if (!pendingCommentDelete.value || commentDeleteLoading.value) return;
        commentDeleteLoading.value = true;
        const comment = pendingCommentDelete.value;
        try {
            const url = commentDeletePath.replace("__id__", comment.id);
            const res = await fetch(url, { method: "POST" });
            const data = await res.json();
            if (data?.success) {
                comments.value = comments.value.filter(
                    (c) => c.id !== comment.id,
                );
                pendingCommentDelete.value = null;
                toast.success(t("photo.galleries.admin.comments.deleted"));
            } else {
                toast.error(t("shared.common.error"));
            }
        } finally {
            commentDeleteLoading.value = false;
        }
    }

    function commentsForItem(itemId) {
        return comments.value.filter((c) => c.itemId === itemId);
    }

    return {
        pendingCommentDelete,
        commentDeleteLoading,
        askDeleteComment,
        confirmDeleteComment,
        commentsForItem,
    };
}
