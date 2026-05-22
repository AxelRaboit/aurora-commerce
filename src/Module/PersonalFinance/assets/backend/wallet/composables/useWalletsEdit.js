import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";

/**
 * Wallet edit flow — same shape as useWalletsCreate but parametrized by
 * an existing wallet. The parent supplies an `onUpdated(wallet)` callback
 * which is responsible for patching the local list.
 */
export function useWalletsEdit(updatePath, onUpdated) {
    const { t } = useI18n();

    const showEdit = ref(false);
    const editingWallet = ref(null);
    const editForm = ref({
        name: "",
        startBalance: "0.00",
        mode: "budget",
        showOnDashboard: true,
        position: 0,
    });
    const editErrors = ref({});
    const editLoading = ref(false);

    function openEdit(wallet) {
        editingWallet.value = wallet;
        editForm.value = {
            name: wallet.name,
            startBalance: wallet.startBalance,
            mode: wallet.mode,
            showOnDashboard: wallet.showOnDashboard ?? true,
            position: wallet.position ?? 0,
        };
        editErrors.value = {};
        showEdit.value = true;
    }

    async function submitEdit() {
        if (!editingWallet.value || editLoading.value) return;
        editLoading.value = true;
        editErrors.value = {};
        editForm.value.startBalance = evaluateAmount(
            editForm.value.startBalance,
        );
        try {
            const url = buildPath(updatePath, { id: editingWallet.value.id });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(editForm.value),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                editErrors.value = payload?.errors ?? {};
                return;
            }
            onUpdated(payload.wallet);
            toast.success(t("personal_finance.wallets.updated"));
            showEdit.value = false;
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            editLoading.value = false;
        }
    }

    return {
        showEdit,
        editingWallet,
        editForm,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
    };
}
