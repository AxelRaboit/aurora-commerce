import { ref, computed, unref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Returns the absolute (year, monthIndex) breakdown of an "YYYY-MM"
 * string. Defaults to the current month when the input is missing.
 */
function decomposeMonth(iso) {
    if (typeof iso === "string" && /^\d{4}-\d{2}/.test(iso)) {
        const [y, m] = iso.split("-").map(Number);
        return { year: y, monthIndex: m - 1 };
    }
    const now = new Date();
    return { year: now.getFullYear(), monthIndex: now.getMonth() };
}

function todayMonthIso() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
}

/**
 * Reset-month flow driver. Owns:
 * - the modal state (`show`, `clearBudget`, `cascade`)
 * - the request lifecycle (POST + toast)
 * - the cascade preview (`cascadeEndMonth`, `cascadePreviewCount`) so
 *   the SFC stays pure rendering.
 *
 * `monthRef` is a Ref<string> exposing the currently-displayed
 * "YYYY-MM" so the previews react to user navigation. The cap-at-today
 * policy is enforced server-side; the previews here mirror it 1-1 so
 * the user sees the same shape.
 */
export function useBudgetMonthReset({ resetPath, monthRef, onReset }) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const show = ref(false);
    const clearBudget = ref(false);
    const cascade = ref(false);

    const cascadeEndMonth = computed(() => {
        if (!cascade.value) return unref(monthRef);
        const current = unref(monthRef);
        const todayIso = todayMonthIso();
        // If the user is targeting a future month, cascade collapses to
        // a single-month reset (same as the server-side cap).
        return current > todayIso ? current : todayIso;
    });

    const cascadePreviewCount = computed(() => {
        if (!cascade.value) return 1;
        const from = decomposeMonth(unref(monthRef));
        const to = decomposeMonth(cascadeEndMonth.value);
        const fromAbs = from.year * 12 + from.monthIndex;
        const toAbs = to.year * 12 + to.monthIndex;
        return Math.max(1, toAbs - fromAbs + 1);
    });

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

    return {
        show, clearBudget, cascade, loading,
        cascadeEndMonth, cascadePreviewCount,
        open, confirm,
    };
}
