import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";

function emptyForm() {
    return {
        walletId: null,
        categoryId: null,
        type: "expense",
        amount: "",
        description: "",
        dayOfMonth: 1,
        active: true,
    };
}

export function useRecurringForm(createPath, updatePath, onSaved) {
    const { t } = useI18n();

    const show = ref(false);
    const isEditing = ref(false);
    const editingId = ref(null);
    const form = ref(emptyForm());
    const errors = ref({});
    const loading = ref(false);

    function openCreate() {
        isEditing.value = false;
        editingId.value = null;
        form.value = emptyForm();
        errors.value = {};
        show.value = true;
    }

    function openEdit(rec) {
        isEditing.value = true;
        editingId.value = rec.id;
        form.value = {
            walletId: rec.walletId,
            categoryId: rec.categoryId ?? null,
            type: rec.type,
            amount: rec.amount,
            description: rec.description ?? "",
            dayOfMonth: rec.dayOfMonth,
            active: rec.active,
        };
        errors.value = {};
        show.value = true;
    }

    async function submit() {
        if (loading.value) return;
        loading.value = true;
        errors.value = {};
        form.value.amount = evaluateAmount(form.value.amount);

        const url = isEditing.value ? buildPath(updatePath, { id: editingId.value }) : createPath;

        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { Accept: "application/json", "Content-Type": "application/json" },
                body: JSON.stringify({
                    ...form.value,
                    description: form.value.description || null,
                    categoryId: form.value.categoryId || null,
                }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                errors.value = payload?.errors ?? {};
                return;
            }
            toast.success(t(isEditing.value ? "personal_finance.recurring.updated_recurring" : "personal_finance.recurring.created_recurring"));
            show.value = false;
            onSaved?.(payload.recurring);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { show, isEditing, form, errors, loading, openCreate, openEdit, submit };
}
