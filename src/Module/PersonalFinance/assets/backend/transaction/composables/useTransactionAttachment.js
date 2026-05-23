import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Attachment ops (upload + delete) on an existing transaction. The
 * transaction must already be persisted (the backend rejects upload to
 * a non-saved tx). Spendly's flow is identical: receipts are added
 * AFTER the transaction is saved.
 */
export function useTransactionAttachment(uploadPath, deletePath, serveBaseUrl, onChanged) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    async function upload(transactionId, file) {
        if (!file) return null;
        const formData = new FormData();
        formData.append("file", file);
        const payload = await request(buildPath(uploadPath, { id: transactionId }), null, { rawBody: formData });
        if (!payload) return null;
        if (payload.success === false) {
            const message = payload.errors?.file ?? "shared.common.error";
            toast.error(t(message));
            return null;
        }
        toast.success(t("personal_finance.transactions.attachment.uploaded"));
        onChanged?.(payload.transaction);
        return payload.transaction ?? null;
    }

    async function remove(transactionId) {
        const payload = await request(buildPath(deletePath, { id: transactionId }));
        if (!payload || payload.success === false) return;
        toast.success(t("personal_finance.transactions.attachment.removed"));
        onChanged?.(payload.transaction);
    }

    function serveUrl(transactionId) {
        return buildPath(serveBaseUrl, { id: transactionId });
    }

    return { loading, upload, remove, serveUrl };
}
