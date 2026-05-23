import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

/**
 * Confirm + delete a transfer (both legs at once). Distinct from useDelete
 * because the URL placeholder is `__transferId__` (string UUID) rather than
 * the generic `__id__` (integer).
 */
export function useTransfersDelete(deletePath, onSuccess) {
    const { t } = useI18n();
    const pendingDelete = ref(null);
    const loading = ref(false);

    function confirm(transferContext) {
        pendingDelete.value = transferContext;
    }

    async function submit() {
        if (loading.value || !pendingDelete.value) return;
        loading.value = true;
        try {
            const url = buildPath(deletePath, {
                transferId: pendingDelete.value.transferId,
            });
            const response = await fetch(url, { method: HttpMethod.Post });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (data.success) {
                pendingDelete.value = null;
                toast.success(t("personal_finance.transfers.deleted"));
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
