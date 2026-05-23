import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";

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

    const show = ref(false);
    const form = ref(emptySplitForm());
    const errors = ref({});
    const loading = ref(false);
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
        if (loading.value || !targetWalletId.value) return;
        loading.value = true;
        errors.value = {};
        const normalizedParts = form.value.parts.map((p) => ({
            categoryId: p.categoryId,
            amount: evaluateAmount(p.amount),
            description: p.description || null,
        }));
        try {
            const url = buildPath(createPath, { walletId: targetWalletId.value });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    type: form.value.type,
                    date: form.value.date,
                    description: form.value.description || null,
                    parts: normalizedParts,
                }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                errors.value = payload?.errors ?? {};
                return;
            }
            toast.success(t("personal_finance.splits.created"));
            show.value = false;
            onCreated?.(payload.splitId);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
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
