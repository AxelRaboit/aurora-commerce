import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";

export function useGoalDeposit(depositPath, onDeposited) {
    const { t } = useI18n();

    const show = ref(false);
    const target = ref(null);
    const amount = ref("");
    const errors = ref({});
    const loading = ref(false);

    function open(goal) {
        target.value = goal;
        amount.value = "";
        errors.value = {};
        show.value = true;
    }

    async function submit() {
        if (loading.value || !target.value) return;
        loading.value = true;
        errors.value = {};
        const normalized = evaluateAmount(amount.value);
        try {
            const url = buildPath(depositPath, { id: target.value.id });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ amount: normalized }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                errors.value = payload?.errors ?? {};
                return;
            }
            toast.success(t("personal_finance.goals.deposit_success"));
            show.value = false;
            onDeposited?.(payload.goal);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { show, target, amount, errors, loading, open, submit };
}
