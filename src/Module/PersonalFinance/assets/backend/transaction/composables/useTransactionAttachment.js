import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

/**
 * Attachment ops (upload + delete) on an existing transaction. The
 * transaction must already be persisted (the backend rejects upload to
 * a non-saved tx). Spendly's flow is identical: receipts are added
 * AFTER the transaction is saved.
 */
export function useTransactionAttachment(uploadPath, deletePath, serveBaseUrl, onChanged) {
    const { t } = useI18n();
    const loading = ref(false);

    async function upload(transactionId, file) {
        if (!file || loading.value) return null;
        loading.value = true;
        const formData = new FormData();
        formData.append("file", file);
        try {
            const url = buildPath(uploadPath, { id: transactionId });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                body: formData,
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                const message = payload?.errors?.file ?? "shared.common.error";
                toast.error(t(message));
                return null;
            }
            toast.success(t("personal_finance.transactions.attachment.uploaded"));
            onChanged?.(payload.transaction);
            return payload.transaction ?? null;
        } catch {
            toast.error(t("shared.common.error"));
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function remove(transactionId) {
        if (loading.value) return;
        loading.value = true;
        try {
            const url = buildPath(deletePath, { id: transactionId });
            const response = await fetch(url, { method: HttpMethod.Post });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                toast.error(t("shared.common.error"));
                return;
            }
            toast.success(t("personal_finance.transactions.attachment.removed"));
            onChanged?.(payload.transaction);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    function serveUrl(transactionId) {
        return buildPath(serveBaseUrl, { id: transactionId });
    }

    return { loading, upload, remove, serveUrl };
}
