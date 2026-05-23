import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";

/**
 * Unified create + edit composable for wallet-to-wallet transfers.
 *
 * In edit mode, the wallet selectors are read-only — the TransferService
 * intentionally ignores fromWalletId/toWalletId on update (changing
 * wallets requires deleting and recreating the transfer).
 */
export function useTransfersForm(
    createPath,
    updatePath,
    showPath,
    onSaved,
) {
    const { t } = useI18n();

    const show = ref(false);
    const isEditing = ref(false);
    const editingTransferId = ref(null);
    const form = ref(emptyTransferForm());
    const errors = ref({});
    const loading = ref(false);

    function emptyTransferForm() {
        const today = new Date().toISOString().slice(0, 10);
        return {
            fromWalletId: null,
            toWalletId: null,
            amount: "",
            date: today,
            description: "",
        };
    }

    function openCreate(defaultFromWalletId = null) {
        isEditing.value = false;
        editingTransferId.value = null;
        form.value = emptyTransferForm();
        if (defaultFromWalletId) form.value.fromWalletId = defaultFromWalletId;
        errors.value = {};
        show.value = true;
    }

    async function openEdit(transferId) {
        if (loading.value) return;
        loading.value = true;
        errors.value = {};
        try {
            const url = buildPath(showPath, { transferId });
            const response = await fetch(url, {
                method: HttpMethod.Get,
                headers: { Accept: "application/json" },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false || !payload?.transfer) {
                toast.error(t("shared.common.error"));
                return;
            }
            isEditing.value = true;
            editingTransferId.value = transferId;
            form.value = {
                fromWalletId: payload.transfer.fromWalletId,
                toWalletId: payload.transfer.toWalletId,
                amount: payload.transfer.amount,
                date: payload.transfer.date,
                description: payload.transfer.description ?? "",
            };
            show.value = true;
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    async function submit() {
        if (loading.value) return;
        loading.value = true;
        errors.value = {};
        form.value.amount = evaluateAmount(form.value.amount);
        try {
            const url = isEditing.value
                ? buildPath(updatePath, { transferId: editingTransferId.value })
                : createPath;
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    fromWalletId: form.value.fromWalletId,
                    toWalletId: form.value.toWalletId,
                    amount: form.value.amount,
                    date: form.value.date,
                    description: form.value.description || null,
                }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                errors.value = payload?.errors ?? {};
                return;
            }
            toast.success(
                t(
                    isEditing.value
                        ? "personal_finance.transfers.updated"
                        : "personal_finance.transfers.created",
                ),
            );
            show.value = false;
            onSaved?.(payload.transferId);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return {
        show,
        isEditing,
        editingTransferId,
        form,
        errors,
        loading,
        openCreate,
        openEdit,
        submit,
    };
}
