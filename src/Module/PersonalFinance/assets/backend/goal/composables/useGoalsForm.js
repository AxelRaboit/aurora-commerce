import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

function emptyGoalForm(extraFields = {}) {
    const extras = Object.fromEntries(
        Object.keys(extraFields).map((k) => [
            k,
            extraFields[k]?.default ?? null,
        ]),
    );
    return {
        name: "",
        targetAmount: "",
        walletId: null,
        categoryId: null,
        deadline: "",
        color: "#6366f1",
        trackingMode: "expense_only",
        ...extras,
    };
}

function pickExtras(extraFields, source) {
    return Object.fromEntries(
        Object.keys(extraFields).map((k) => [
            k,
            source?.[k] ?? extraFields[k]?.default ?? null,
        ]),
    );
}

/**
 * Unified create + edit composable for goals. Deposit lives in its own
 * composable (useGoalDeposit) because the form shape and lifecycle
 * (auto-tracked vs manual) differ enough to keep them separate.
 */
export function useGoalsForm(createPath, updatePath, onSaved, { extraFields = {} } = {}) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const show = ref(false);
    const isEditing = ref(false);
    const editingId = ref(null);
    const form = ref(emptyGoalForm(extraFields));
    const errors = ref({});

    function openCreate() {
        isEditing.value = false;
        editingId.value = null;
        form.value = emptyGoalForm(extraFields);
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
            trackingMode: goal.trackingMode ?? "expense_only",
            ...pickExtras(extraFields, goal),
        };
        errors.value = {};
        show.value = true;
    }

    async function submit() {
        errors.value = {};
        form.value.targetAmount = evaluateAmount(form.value.targetAmount);

        const url = isEditing.value ? buildPath(updatePath, { id: editingId.value }) : createPath;
        const payload = await request(url, {
            ...form.value,
            walletId: form.value.walletId || null,
            categoryId: form.value.categoryId || null,
            deadline: form.value.deadline || null,
            color: form.value.color || null,
            trackingMode: form.value.trackingMode,
        });
        if (!payload) return;
        if (payload.success === false) {
            errors.value = payload.errors ?? {};
            return;
        }
        toast.success(t(isEditing.value ? "personal_finance.goals.updated" : "personal_finance.goals.created"));
        show.value = false;
        onSaved?.(payload.goal);
    }

    return { show, isEditing, form, errors, loading, openCreate, openEdit, submit };
}
