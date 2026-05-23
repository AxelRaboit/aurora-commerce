import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Confirm + delete a transfer (both legs at once). Distinct from useDelete
 * because the URL placeholder is `__transferId__` (string UUID) rather than
 * the generic `__id__` (integer).
 */
export function useTransfersDelete(deletePath, onSuccess) {
    const { t } = useI18n();
    const { loading, request } = useRequest();
    const pendingDelete = ref(null);

    function confirm(transferContext) {
        pendingDelete.value = transferContext;
    }

    async function submit() {
        if (!pendingDelete.value) return;
        const payload = await request(
            buildPath(deletePath, {
                transferId: pendingDelete.value.transferId,
            }),
        );
        if (!payload || !payload.success) return;
        pendingDelete.value = null;
        toast.success(t("personal_finance.transfers.deleted"));
        onSuccess?.();
    }

    return { pendingDelete, loading, confirm, submit };
}
