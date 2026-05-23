import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Confirm + delete a split (every leg sharing the splitId). Distinct
 * from useDelete because the URL placeholder is `__splitId__`.
 */
export function useSplitsDelete(deletePath, onSuccess) {
    const { t } = useI18n();
    const { loading, request } = useRequest();
    const pendingDelete = ref(null);

    function confirm(splitContext) {
        pendingDelete.value = splitContext;
    }

    async function submit() {
        if (!pendingDelete.value) return;
        const payload = await request(buildPath(deletePath, { splitId: pendingDelete.value.splitId }));
        if (!payload || !payload.success) return;
        pendingDelete.value = null;
        toast.success(t("personal_finance.splits.deleted"));
        onSuccess?.();
    }

    return { pendingDelete, loading, confirm, submit };
}
