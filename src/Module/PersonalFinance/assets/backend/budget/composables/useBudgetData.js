import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Holds the current budget payload (sections + balance) and lets us
 * refresh it after any item mutation without a page reload.
 *
 * When the backend reports `rolledOver > 0` (the budget was just
 * auto-created from the previous month's `repeatNextMonth` items),
 * fire a one-off success toast so the user understands why lines
 * appeared without manual entry.
 */
export function useBudgetData(showBudgetPath, initial) {
    const payload = ref(initial ?? { budget: null, sections: {}, balance: { current: "0.00", month: "0.00", rollingStart: "0.00" } });
    const { loading, request } = useRequest();
    const { t } = useI18n();

    function notifyIfRolledOver(data) {
        const count = data?.rolledOver ?? 0;
        if (count > 0) {
            toast.success(t("personal_finance.budget.rolled_over_toast", { count }));
        }
    }

    async function refresh(walletId, month) {
        if (!walletId) return;
        const url = buildPath(showBudgetPath, { walletId }) + `?month=${encodeURIComponent(month)}`;
        const data = await request(url, null, HttpMethod.Get);
        if (data && data.success !== false) {
            payload.value = data;
            notifyIfRolledOver(data);
        }
    }

    // Fire the rollover toast on initial SSR-rendered payload too —
    // the user shouldn't have to click refresh to see the message.
    notifyIfRolledOver(payload.value);

    return { payload, loading, refresh };
}
