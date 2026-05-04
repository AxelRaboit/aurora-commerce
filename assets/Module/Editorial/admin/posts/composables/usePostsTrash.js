import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function usePostsTrash(props, removePost, setTrashedFilter) {
    const { t } = useI18n();

    const emptyingTrash = ref(false);
    const confirmEmptyTrash = ref(false);

    async function emptyTrash() {
        if (!props.emptyTrashPath) return;
        emptyingTrash.value = true;
        try {
            const response = await fetch(props.emptyTrashPath, {
                method: HttpMethod.Post,
            });
            const data = await response.json();
            if (data.success) {
                toast.success(
                    t("admin.posts.emptyTrashDone", { count: data.count }),
                );
                setTrashedFilter(true);
            } else toast.error(t("shared.common.error"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            emptyingTrash.value = false;
            confirmEmptyTrash.value = false;
        }
    }

    async function restorePost(post) {
        try {
            const response = await fetch(
                buildPath(props.restorePath, { id: post.id }),
                { method: HttpMethod.Post },
            );
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (data.success) {
                removePost(post.id);
                toast.success(t("admin.posts.restored"));
            }
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    return { emptyingTrash, confirmEmptyTrash, emptyTrash, restorePost };
}
