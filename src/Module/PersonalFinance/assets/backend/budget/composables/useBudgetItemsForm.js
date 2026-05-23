import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";

function emptyItemForm() {
    return {
        section: "expenses",
        label: "",
        plannedAmount: "",
        carriedOver: "0.00",
        categoryId: null,
        notes: "",
        repeatNextMonth: false,
        position: 0,
    };
}

/**
 * Unified create + edit composable for budget items. Both operations
 * land in the same modal; isEditing tracks the mode.
 */
export function useBudgetItemsForm({ createPath, updatePath, deletePath, onChanged }) {
    const { t } = useI18n();

    const show = ref(false);
    const isEditing = ref(false);
    const editingItemId = ref(null);
    const targetWalletId = ref(null);
    const targetMonth = ref(null);
    const form = ref(emptyItemForm());
    const errors = ref({});
    const loading = ref(false);

    function openCreate({ walletId, month, section }) {
        targetWalletId.value = walletId;
        targetMonth.value = month;
        form.value = { ...emptyItemForm(), section: section ?? "expenses" };
        isEditing.value = false;
        editingItemId.value = null;
        errors.value = {};
        show.value = true;
    }

    function openEdit({ walletId, month, item }) {
        targetWalletId.value = walletId;
        targetMonth.value = month;
        form.value = {
            section: item.section,
            label: item.label,
            plannedAmount: item.plannedAmount,
            carriedOver: item.carriedOver,
            categoryId: item.categoryId ?? null,
            notes: item.notes ?? "",
            repeatNextMonth: item.repeatNextMonth,
            position: item.position ?? 0,
        };
        isEditing.value = true;
        editingItemId.value = item.id;
        errors.value = {};
        show.value = true;
    }

    async function submit() {
        if (loading.value) return;
        loading.value = true;
        errors.value = {};
        form.value.plannedAmount = evaluateAmount(form.value.plannedAmount);
        form.value.carriedOver = evaluateAmount(form.value.carriedOver) || "0.00";

        const url = isEditing.value
            ? buildPath(updatePath, { id: editingItemId.value })
            : buildPath(createPath, { walletId: targetWalletId.value });

        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    ...form.value,
                    month: targetMonth.value,
                    notes: form.value.notes || null,
                    categoryId: form.value.categoryId || null,
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
                        ? "personal_finance.budget.updated"
                        : "personal_finance.budget.created",
                ),
            );
            show.value = false;
            onChanged?.();
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    const pendingDelete = ref(null);
    const deleteLoading = ref(false);

    function confirmDelete(item) {
        pendingDelete.value = item;
    }

    async function doDelete() {
        if (deleteLoading.value || !pendingDelete.value) return;
        deleteLoading.value = true;
        try {
            const url = buildPath(deletePath, { id: pendingDelete.value.id });
            const response = await fetch(url, { method: HttpMethod.Post });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                toast.error(t("shared.common.error"));
                return;
            }
            pendingDelete.value = null;
            toast.success(t("personal_finance.budget.deleted"));
            onChanged?.();
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            deleteLoading.value = false;
        }
    }

    return {
        show,
        isEditing,
        form,
        errors,
        loading,
        openCreate,
        openEdit,
        submit,
        pendingDelete,
        deleteLoading,
        confirmDelete,
        doDelete,
    };
}
