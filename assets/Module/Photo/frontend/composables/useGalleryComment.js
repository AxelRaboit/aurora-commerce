import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

// showCommentBox and commentDraft are passed in as refs (shared with useGalleryLightbox)
export function useGalleryComment({
    commentPath,
    displayedItems,
    lightboxIndex,
    showCommentBox,
    commentDraft,
    visitorName,
    visitorEmail,
    identityKnown,
}) {
    const { t } = useI18n();

    const commentSending = ref(false);
    const commentNameError = ref("");
    const commentEmailError = ref("");

    async function submitComment() {
        if (lightboxIndex.value === null) return;
        commentNameError.value = "";
        commentEmailError.value = "";
        if (!commentDraft.value.trim()) {
            toast.error(t("photo.frontend.comments.contentRequired"));
            return;
        }
        if (!visitorName.value.trim()) {
            commentNameError.value = t("photo.frontend.comments.nameRequired");
            return;
        }
        if (!visitorEmail.value.trim()) {
            commentEmailError.value = t(
                "photo.frontend.comments.emailRequired",
            );
            return;
        }
        const itemId = displayedItems.value[lightboxIndex.value].id;
        commentSending.value = true;
        try {
            const response = await fetch(
                buildPath(commentPath, { id: itemId }),
                {
                    method: HttpMethod.Post,
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        content: commentDraft.value,
                        name: visitorName.value,
                        email: visitorEmail.value,
                    }),
                },
            );
            const data = await response.json();
            if (!data?.success) {
                const errors = translateServerErrors(t, data?.errors);
                if (errors.visitorEmail)
                    commentEmailError.value = errors.visitorEmail;
                else toast.error(t("shared.common.error"));
                return;
            }
            identityKnown.value = true;
            toast.success(t("photo.frontend.comments.sent"));
            commentDraft.value = "";
            showCommentBox.value = false;
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            commentSending.value = false;
        }
    }

    return {
        commentSending,
        commentNameError,
        commentEmailError,
        submitComment,
    };
}
