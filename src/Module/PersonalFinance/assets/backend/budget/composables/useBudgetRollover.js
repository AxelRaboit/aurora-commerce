import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Drives the "Reporter les lignes du mois précédent" banner action.
 * One-shot per month — the server flips `wasRolledOver` to true so
 * the banner disappears on the next refresh. Subsequent clicks would
 * be no-ops anyway (Manager guards against re-rollover).
 */
export function useBudgetRollover({ rolloverPath, onRolledOver }) {
    const { t } = useI18n();
    const { loading, request } = useRequest();
    const lastCount = ref(0);

    async function rollover(walletId, month) {
        if (!walletId || !month) return;

        const response = await request(
            buildPath(rolloverPath, { walletId }),
            { month },
        );
        if (!response) return;
        if (response.success === false) {
            toast.error(t("shared.common.error"));
            return;
        }

        const count = response.rolledOverCount ?? 0;
        lastCount.value = count;
        toast.success(t("personal_finance.budget.rolled_over_toast", { count }, count));
        onRolledOver?.(response);
    }

    return { loading, lastCount, rollover };
}
