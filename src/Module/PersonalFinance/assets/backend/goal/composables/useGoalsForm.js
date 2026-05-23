import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";

function emptyGoalForm() {
    return {
        name: "",
        targetAmount: "",
        walletId: null,
        categoryId: null,
        deadline: "",
        color: "#6366f1",
    };
}

/**
 * Unified create + edit composable for goals. Deposit lives in its own
 * composable (useGoalDeposit) because the form shape and lifecycle
 * (auto-tracked vs manual) differ enough to keep them separate.
 */
export function useGoalsForm(createPath, updatePath, onSaved) {
    const { t } = useI18n();

    const show = ref(false);
    const isEditing = ref(false);
    const editingId = ref(null);
    const form = ref(emptyGoalForm());
    const errors = ref({});
    const loading = ref(false);

    function openCreate() {
        isEditing.value = false;
        editingId.value = null;
        form.value = emptyGoalForm();
        errors.value = {};
        show.value = true;
    }

    function openEdit(goal) {
        isEditing.value = true;
        editingId.value = goal.id;
        form.value = {
            name: goal.name,
            targetAmount: goal.targetAmount,
            walletId: goal.walletId ?? null,
            categoryId: goal.categoryId ?? null,
            deadline: goal.deadline ?? "",
            color: goal.color ?? "#6366f1",
        };
        errors.value = {};
        show.value = true;
    }

    async function submit() {
        if (loading.value) return;
        loading.value = true;
        errors.value = {};
        form.value.targetAmount = evaluateAmount(form.value.targetAmount);

        const url = isEditing.value
            ? buildPath(updatePath, { id: editingId.value })
            : createPath;

        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    name: form.value.name,
                    targetAmount: form.value.targetAmount,
                    walletId: form.value.walletId || null,
                    categoryId: form.value.categoryId || null,
                    deadline: form.value.deadline || null,
                    color: form.value.color || null,
                }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                errors.value = payload?.errors ?? {};
                return;
            }
            toast.success(
                t(
                    isEditing.value
                        ? "personal_finance.goals.updated"
                        : "personal_finance.goals.created",
                ),
            );
            show.value = false;
            onSaved?.(payload.goal);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { show, isEditing, form, errors, loading, openCreate, openEdit, submit };
}
