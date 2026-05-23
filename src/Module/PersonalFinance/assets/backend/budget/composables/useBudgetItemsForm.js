import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

function emptyItemForm(extraFields = {}) {
    const extras = Object.fromEntries(
        Object.keys(extraFields).map((k) => [
            k,
            extraFields[k]?.default ?? null,
        ]),
    );
    return {
        section: "expenses",
        label: "",
        plannedAmount: "",
        carriedOver: "0.00",
        categoryId: null,
        notes: "",
        repeatNextMonth: false,
        position: 0,
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
 * Unified create + edit composable for budget items. Both operations
 * land in the same modal; isEditing tracks the mode.
 *
 * Two `useRequest` instances are used because the submit form flow and
 * the delete confirmation flow need independent loading guards (so a
 * confirm-delete spinner doesn't disable the form-edit button and
 * vice-versa).
 */
export function useBudgetItemsForm({
    createPath,
    updatePath,
    deletePath,
    onChanged,
    extraFields = {},
}) {
    const { t } = useI18n();
    const { loading, request } = useRequest();
    const { loading: deleteLoading, request: deleteRequest } = useRequest();

    const show = ref(false);
    const isEditing = ref(false);
    const editingItemId = ref(null);
    const targetWalletId = ref(null);
    const targetMonth = ref(null);
    const form = ref(emptyItemForm(extraFields));
    const errors = ref({});

    function openCreate({ walletId, month, section }) {
        targetWalletId.value = walletId;
        targetMonth.value = month;
        form.value = {
            ...emptyItemForm(extraFields),
            section: section ?? "expenses",
        };
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
            ...pickExtras(extraFields, item),
        };
        isEditing.value = true;
        editingItemId.value = item.id;
        errors.value = {};
        show.value = true;
    }

    async function submit() {
        errors.value = {};
        form.value.plannedAmount = evaluateAmount(form.value.plannedAmount);
        form.value.carriedOver =
            evaluateAmount(form.value.carriedOver) || "0.00";

        const url = isEditing.value
            ? buildPath(updatePath, { id: editingItemId.value })
            : buildPath(createPath, { walletId: targetWalletId.value });

        const payload = await request(url, {
            ...form.value,
            month: targetMonth.value,
            notes: form.value.notes || null,
            categoryId: form.value.categoryId || null,
        });
        if (!payload) return;
        if (payload.success === false) {
            errors.value = payload.errors ?? {};
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
    }

    const pendingDelete = ref(null);

    function confirmDelete(item) {
        pendingDelete.value = item;
    }

    async function doDelete() {
        if (!pendingDelete.value) return;
        const payload = await deleteRequest(
            buildPath(deletePath, { id: pendingDelete.value.id }),
        );
        if (!payload || payload.success === false) return;
        pendingDelete.value = null;
        toast.success(t("personal_finance.budget.deleted"));
        onChanged?.();
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
