import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function useWalletBalance(balancePath, initial) {
    const balance = ref(initial ?? { current: "0.00", month: "0.00", rollingStart: "0.00" });
    const { loading, request } = useRequest();

    async function refresh(walletId, month = null) {
        if (!walletId) return;
        const url = buildPath(balancePath, { walletId }) + (month ? `?month=${encodeURIComponent(month)}` : "");
        const payload = await request(url, null, HttpMethod.Get);
        if (payload && payload.success !== false && payload.balance) {
            balance.value = payload.balance;
        }
    }

    return { balance, loading, refresh };
}
