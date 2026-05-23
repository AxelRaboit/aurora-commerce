import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Holds the current budget payload (sections + balance + rollover
 * banner state) and lets us refresh it after any item mutation
 * without a page reload.
 *
 * Rollover is no longer implicit — the user triggers it explicitly
 * from the banner displayed in the page (see `useBudgetRollover`).
 * This composable just transports the `eligibleRolloverCount` +
 * `wasRolledOver` flags from the server.
 */
export function useBudgetData(showBudgetPath, initial) {
    const payload = ref(
        initial ?? {
            budget: null,
            sections: {},
            balance: { current: "0.00", month: "0.00", rollingStart: "0.00" },
            eligibleRolloverCount: 0,
            wasRolledOver: false,
        },
    );
    const { loading, request } = useRequest();

    async function refresh(walletId, month) {
        if (!walletId) return;
        const url =
            buildPath(showBudgetPath, { walletId }) +
            `?month=${encodeURIComponent(month)}`;
        const data = await request(url, null, HttpMethod.Get);
        if (data && data.success !== false) {
            payload.value = data;
        }
    }

    return { payload, loading, refresh };
}
