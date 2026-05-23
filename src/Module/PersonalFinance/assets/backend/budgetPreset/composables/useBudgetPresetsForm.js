import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

function emptyForm() {
    return {
        name: "",
        description: "",
        items: [],
    };
}

function emptyItem(section = "expenses") {
    return {
        section,
        label: "",
        plannedAmount: "",
        categoryId: null,
        position: 0,
        notes: "",
    };
}

/**
 * Unified create + edit composable for budget presets. Handles the
 * dynamic items sub-list (add, remove, reorder via simple position
 * field — no drag-and-drop yet, matches Budget items' UX).
 */
export function useBudgetPresetsForm(createPath, updatePath, onSaved) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const show = ref(false);
    const isEditing = ref(false);
    const editingId = ref(null);
    const targetWalletId = ref(null);
    const form = ref(emptyForm());
    const errors = ref({});

    function openCreate(walletId, defaultSection = "expenses") {
        isEditing.value = false;
        editingId.value = null;
        targetWalletId.value = walletId;
        form.value = emptyForm();
        form.value.items.push(emptyItem(defaultSection));
        errors.value = {};
        show.value = true;
    }

    function openEdit(preset) {
        isEditing.value = true;
        editingId.value = preset.id;
        targetWalletId.value = preset.walletId;
        form.value = {
            name: preset.name ?? "",
            description: preset.description ?? "",
            items: (preset.items ?? []).map((item) => ({
                section: item.section,
                label: item.label,
                plannedAmount: item.plannedAmount,
                categoryId: item.categoryId ?? null,
                position: item.position ?? 0,
                notes: item.notes ?? "",
            })),
        };
        errors.value = {};
        show.value = true;
    }

    function addItem(section = "expenses") {
        form.value.items.push(emptyItem(section));
    }

    function removeItem(index) {
        form.value.items.splice(index, 1);
    }

    async function submit() {
        errors.value = {};

        const payload = {
            name: form.value.name,
            description: form.value.description || null,
            items: form.value.items.map((row, idx) => ({
                section: row.section,
                label: row.label,
                plannedAmount: evaluateAmount(row.plannedAmount) || "0.00",
                categoryId: row.categoryId || null,
                position: idx,
                notes: row.notes || null,
            })),
        };

        const url = isEditing.value
            ? buildPath(updatePath, { id: editingId.value })
            : buildPath(createPath, { walletId: targetWalletId.value });

        const response = await request(url, payload);
        if (!response) return;
        if (response.success === false) {
            errors.value = response.errors ?? {};
            return;
        }
        toast.success(
            t(isEditing.value ? "personal_finance.budget_presets.updated" : "personal_finance.budget_presets.created"),
        );
        show.value = false;
        onSaved?.(response.preset);
    }

    return { show, isEditing, form, errors, loading, openCreate, openEdit, addItem, removeItem, submit };
}
