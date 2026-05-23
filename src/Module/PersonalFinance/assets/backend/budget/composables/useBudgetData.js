import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

/**
 * Holds the current budget payload (sections + balance) and lets us
 * refresh it after any item mutation without a page reload.
 */
export function useBudgetData(showBudgetPath, initial) {
    const payload = ref(initial ?? { budget: null, sections: {}, balance: { current: "0.00", month: "0.00", rollingStart: "0.00" } });
    const loading = ref(false);

    async function refresh(walletId, month) {
        if (!walletId) return;
        loading.value = true;
        try {
            const url = buildPath(showBudgetPath, { walletId }) + `?month=${encodeURIComponent(month)}`;
            const response = await fetch(url, {
                method: HttpMethod.Get,
                headers: { Accept: "application/json" },
            });
            const data = await response.json().catch(() => null);
            if (data && data.success !== false) {
                payload.value = data;
            }
        } finally {
            loading.value = false;
        }
    }

    return { payload, loading, refresh };
}
