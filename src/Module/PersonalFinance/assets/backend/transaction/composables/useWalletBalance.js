import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useWalletBalance(balancePath, initial) {
    const { t } = useI18n();
    const balance = ref(initial ?? { current: "0.00", month: "0.00", rollingStart: "0.00" });
    const loading = ref(false);

    async function refresh(walletId, month = null) {
        if (!walletId) return;
        loading.value = true;
        try {
            const url = buildPath(balancePath, { walletId }) + (month ? `?month=${encodeURIComponent(month)}` : "");
            const response = await fetch(url, {
                method: HttpMethod.Get,
                headers: { Accept: "application/json" },
            });
            const payload = await response.json().catch(() => ({}));
            if (response.ok && payload?.success !== false && payload?.balance) {
                balance.value = payload.balance;
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { balance, loading, refresh };
}
