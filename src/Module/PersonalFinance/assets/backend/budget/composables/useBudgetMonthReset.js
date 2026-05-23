import { ref, unref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Reset-month flow driver. Owns the modal state (`show`, `clearBudget`,
 * `cascade`) and the request lifecycle (POST + toast).
 *
 * `monthRef` is a Ref<string> exposing the currently-displayed
 * "YYYY-MM" so submitting picks up navigation changes. There is no
 * front-side preview of how many months a cascade will hit — that
 * depends on per-wallet data (latest tx + latest budget) the server
 * owns. The exact count comes back in the success toast.
 */
export function useBudgetMonthReset({ resetPath, monthRef, onReset }) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const show = ref(false);
    const clearBudget = ref(false);
    const cascade = ref(false);

    function open() {
        clearBudget.value = false;
        cascade.value = false;
        show.value = true;
    }

    async function confirm(walletId) {
        const month = unref(monthRef);
        if (!walletId || !month) return;

        const response = await request(
            buildPath(resetPath, { walletId }),
            { month, clearBudget: clearBudget.value, cascade: cascade.value },
        );
        if (!response) return;
        if (response.success === false) {
            toast.error(t("shared.common.error"));
            return;
        }

        const deleted = response.deletedTransactions ?? 0;
        toast.success(t("personal_finance.budget.reset_done", { count: deleted }, deleted));
        show.value = false;
        onReset?.(response);
    }

    return { show, clearBudget, cascade, loading, open, confirm };
}
