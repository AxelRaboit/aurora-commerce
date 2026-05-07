import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function usePostTypeDelete(deletePath, postTypes, selectedId) {
    const { t } = useI18n();
    const deletingPostType = ref(null);

    async function confirmDeletePostType() {
        const pt = deletingPostType.value;
        if (!pt) return;
        try {
            const response = await fetch(buildPath(deletePath, { id: pt.id }), {
                method: HttpMethod.Post,
            });
            const data = await response.json();
            if (!data.success) {
                toast.error(
                    data.error ? t(data.error) : t("shared.common.error"),
                );
                return;
            }
            postTypes.value = postTypes.value.filter((p) => p.id !== pt.id);
            if (selectedId.value === pt.id)
                selectedId.value = postTypes.value[0]?.id ?? null;
            toast.success(t("shared.common.deleted"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            deletingPostType.value = null;
        }
    }

    return { deletingPostType, confirmDeletePostType };
}
