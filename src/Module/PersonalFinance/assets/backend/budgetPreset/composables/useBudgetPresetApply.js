import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

function currentMonthIso() {
    const now = new Date();
    return `${now.getUTCFullYear()}-${String(now.getUTCMonth() + 1).padStart(2, "0")}`;
}

/**
 * Apply-to-month modal driver. Captures the target month + mode
 * (append/replace) and POSTs to the preset apply endpoint. Used both
 * on the BudgetPresets page and from the Budget page's "Appliquer un
 * preset" button.
 */
export function useBudgetPresetApply(applyPath, onApplied) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const show = ref(false);
    const target = ref(null);
    const month = ref(currentMonthIso());
    const mode = ref("append");
    const errors = ref({});

    function open(preset, initialMonth = null) {
        target.value = preset;
        month.value = initialMonth ?? currentMonthIso();
        mode.value = "append";
        errors.value = {};
        show.value = true;
    }

    async function submit() {
        if (!target.value) return;
        errors.value = {};

        const response = await request(
            buildPath(applyPath, { id: target.value.id }),
            { month: month.value, mode: mode.value },
        );
        if (!response) return;
        if (response.success === false) {
            errors.value = response.errors ?? {};
            return;
        }
        const insertedCount = response.insertedCount ?? 0;
        toast.success(
            t(
                "personal_finance.budget_presets.applied",
                { count: insertedCount },
                insertedCount,
            ),
        );
        show.value = false;
        onApplied?.(response);
    }

    return { show, target, month, mode, errors, loading, open, submit };
}
