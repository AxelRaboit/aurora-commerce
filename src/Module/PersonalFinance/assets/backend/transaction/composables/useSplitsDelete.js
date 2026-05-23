import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

/**
 * Confirm + delete a split (every leg sharing the splitId). Distinct
 * from useDelete because the URL placeholder is `__splitId__`.
 */
export function useSplitsDelete(deletePath, onSuccess) {
    const { t } = useI18n();
    const pendingDelete = ref(null);
    const loading = ref(false);

    function confirm(splitContext) {
        pendingDelete.value = splitContext;
    }

    async function submit() {
        if (loading.value || !pendingDelete.value) return;
        loading.value = true;
        try {
            const url = buildPath(deletePath, {
                splitId: pendingDelete.value.splitId,
            });
            const response = await fetch(url, { method: HttpMethod.Post });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (data.success) {
                pendingDelete.value = null;
                toast.success(t("personal_finance.splits.deleted"));
                onSuccess?.();
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { pendingDelete, loading, confirm, submit };
}
