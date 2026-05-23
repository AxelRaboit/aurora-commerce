import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

function emptyPart() {
    return { categoryId: null, amount: "", description: "" };
}

function emptySplitForm() {
    const today = new Date().toISOString().slice(0, 10);
    return {
        type: "expense",
        date: today,
        description: "",
        parts: [emptyPart(), emptyPart()],
    };
}

/**
 * Create-only composable for splits. Splits can't be updated (matches
 * Spendly) — to change a split, delete it and recreate.
 */
export function useSplitsCreate(createPath, onCreated) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const show = ref(false);
    const form = ref(emptySplitForm());
    const errors = ref({});
    const targetWalletId = ref(null);

    function open(walletId) {
        targetWalletId.value = walletId;
        form.value = emptySplitForm();
        errors.value = {};
        show.value = true;
    }

    function addPart() {
        form.value.parts.push(emptyPart());
    }

    function removePart(index) {
        if (form.value.parts.length <= 2) return;
        form.value.parts.splice(index, 1);
    }

    async function submit() {
        if (!targetWalletId.value) return;
        errors.value = {};
        const normalizedParts = form.value.parts.map((p) => ({
            categoryId: p.categoryId,
            amount: evaluateAmount(p.amount),
            description: p.description || null,
        }));

        const payload = await request(buildPath(createPath, { walletId: targetWalletId.value }), {
            type: form.value.type,
            date: form.value.date,
            description: form.value.description || null,
            parts: normalizedParts,
        });
        if (!payload) return;
        if (payload.success === false) {
            errors.value = payload.errors ?? {};
            return;
        }
        toast.success(t("personal_finance.splits.created"));
        show.value = false;
        onCreated?.(payload.splitId);
    }

    return {
        show,
        form,
        errors,
        loading,
        targetWalletId,
        open,
        addPart,
        removePart,
        submit,
    };
}
