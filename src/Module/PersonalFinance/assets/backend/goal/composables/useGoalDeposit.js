import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function useGoalDeposit(depositPath, onDeposited) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const show = ref(false);
    const target = ref(null);
    const amount = ref("");
    const errors = ref({});

    function open(goal) {
        target.value = goal;
        amount.value = "";
        errors.value = {};
        show.value = true;
    }

    async function submit() {
        if (!target.value) return;
        errors.value = {};
        const normalized = evaluateAmount(amount.value);
        const payload = await request(
            buildPath(depositPath, { id: target.value.id }),
            { amount: normalized },
        );
        if (!payload) return;
        if (payload.success === false) {
            errors.value = payload.errors ?? {};
            return;
        }
        toast.success(t("personal_finance.goals.deposit_success"));
        show.value = false;
        onDeposited?.(payload.goal);
    }

    return { show, target, amount, errors, loading, open, submit };
}
