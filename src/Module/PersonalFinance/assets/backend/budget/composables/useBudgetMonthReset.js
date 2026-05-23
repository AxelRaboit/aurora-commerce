import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Reset-month flow driver: opens a confirmation modal with a checkbox
 * to also wipe the budget items, posts to the reset endpoint, shows a
 * toast with the deleted count, and triggers a refresh on the parent.
 *
 * Other months are never touched (see service docblock). The user is
 * still warned in clear terms — the action is irreversible.
 */
export function useBudgetMonthReset({ resetPath, onReset }) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const show = ref(false);
    const clearBudget = ref(false);

    function open() {
        clearBudget.value = false;
        show.value = true;
    }

    async function confirm(walletId, month) {
        if (!walletId || !month) return;

        const response = await request(
            buildPath(resetPath, { walletId }),
            { month, clearBudget: clearBudget.value },
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

    return { show, clearBudget, loading, open, confirm };
}
