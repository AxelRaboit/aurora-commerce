import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function usePostsPreview(showPath, locales) {
    const { t } = useI18n();
    const previewPost = ref(null);
    const previewLoading = ref(false);

    function frontUrl(post) {
        const locale = locales[0] ?? "fr";
        if (!post.slug || !post.postType?.slug) return null;
        return `/${locale}/${post.postType.slug}/${post.slug}`;
    }

    async function openPreview(post) {
        previewLoading.value = true;
        previewPost.value = null;
        try {
            const response = await fetch(buildPath(showPath, { id: post.id }));
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (data.success) previewPost.value = data.post;
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            previewLoading.value = false;
        }
    }

    return { previewPost, previewLoading, frontUrl, openPreview };
}
