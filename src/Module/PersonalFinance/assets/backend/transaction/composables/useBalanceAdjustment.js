import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

function emptyAdjustmentForm() {
    const today = new Date().toISOString().slice(0, 10);
    return { newBalance: "", date: today, description: "" };
}

export function useBalanceAdjustment(adjustPath, onAdjusted) {
    const { t } = useI18n();
    const show = ref(false);
    const form = ref(emptyAdjustmentForm());
    const errors = ref({});
    const loading = ref(false);
    const targetWalletId = ref(null);

    function open(walletId) {
        targetWalletId.value = walletId;
        form.value = emptyAdjustmentForm();
        errors.value = {};
        show.value = true;
    }

    async function submit() {
        if (loading.value || !targetWalletId.value) return;
        loading.value = true;
        errors.value = {};
        try {
            const url = buildPath(adjustPath, { walletId: targetWalletId.value });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    newBalance: form.value.newBalance,
                    date: form.value.date,
                    description: form.value.description || null,
                }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                errors.value = payload?.errors ?? {};
                return;
            }
            toast.success(t("personal_finance.balance_adjustment.success"));
            show.value = false;
            onAdjusted?.(payload);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { show, form, errors, loading, targetWalletId, open, submit };
}
