import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

function emptyAdjustmentForm() {
    const today = new Date().toISOString().slice(0, 10);
    return { newBalance: "", date: today, description: "" };
}

export function useBalanceAdjustment(adjustPath, onAdjusted) {
    const { t } = useI18n();
    const { loading, request } = useRequest();
    const show = ref(false);
    const form = ref(emptyAdjustmentForm());
    const errors = ref({});
    const targetWalletId = ref(null);

    function open(walletId) {
        targetWalletId.value = walletId;
        form.value = emptyAdjustmentForm();
        errors.value = {};
        show.value = true;
    }

    async function submit() {
        if (!targetWalletId.value) return;
        errors.value = {};
        const payload = await request(
            buildPath(adjustPath, { walletId: targetWalletId.value }),
            {
                newBalance: form.value.newBalance,
                date: form.value.date,
                description: form.value.description || null,
            },
        );
        if (!payload) return;
        if (payload.success === false) {
            errors.value = payload.errors ?? {};
            return;
        }
        toast.success(t("personal_finance.balance_adjustment.success"));
        show.value = false;
        onAdjusted?.(payload);
    }

    return { show, form, errors, loading, targetWalletId, open, submit };
}
