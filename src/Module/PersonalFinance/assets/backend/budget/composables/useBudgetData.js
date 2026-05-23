import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Holds the current budget payload (sections + balance) and lets us
 * refresh it after any item mutation without a page reload.
 */
export function useBudgetData(showBudgetPath, initial) {
    const payload = ref(initial ?? { budget: null, sections: {}, balance: { current: "0.00", month: "0.00", rollingStart: "0.00" } });
    const { loading, request } = useRequest();

    async function refresh(walletId, month) {
        if (!walletId) return;
        const url = buildPath(showBudgetPath, { walletId }) + `?month=${encodeURIComponent(month)}`;
        const data = await request(url, null, HttpMethod.Get);
        if (data && data.success !== false) {
            payload.value = data;
        }
    }

    return { payload, loading, refresh };
}
