import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

function emptyTransferForm(extraFields = {}) {
    const today = new Date().toISOString().slice(0, 10);
    const extras = Object.fromEntries(
        Object.keys(extraFields).map((k) => [
            k,
            extraFields[k]?.default ?? null,
        ]),
    );
    return {
        fromWalletId: null,
        toWalletId: null,
        amount: "",
        date: today,
        description: "",
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
 * Unified create + edit composable for wallet-to-wallet transfers.
 *
 * In edit mode, the wallet selectors are read-only — the TransferService
 * intentionally ignores fromWalletId/toWalletId on update (changing
 * wallets requires deleting and recreating the transfer).
 */
export function useTransfersForm(
    createPath,
    updatePath,
    showPath,
    onSaved,
    { extraFields = {} } = {},
) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const show = ref(false);
    const isEditing = ref(false);
    const editingTransferId = ref(null);
    const form = ref(emptyTransferForm(extraFields));
    const errors = ref({});

    function openCreate(defaultFromWalletId = null) {
        isEditing.value = false;
        editingTransferId.value = null;
        form.value = emptyTransferForm(extraFields);
        if (defaultFromWalletId) form.value.fromWalletId = defaultFromWalletId;
        errors.value = {};
        show.value = true;
    }

    async function openEdit(transferId) {
        errors.value = {};
        const payload = await request(
            buildPath(showPath, { transferId }),
            null,
            HttpMethod.Get,
        );
        if (!payload || payload.success === false || !payload.transfer) return;

        isEditing.value = true;
        editingTransferId.value = transferId;
        form.value = {
            fromWalletId: payload.transfer.fromWalletId,
            toWalletId: payload.transfer.toWalletId,
            amount: payload.transfer.amount,
            date: payload.transfer.date,
            description: payload.transfer.description ?? "",
            ...pickExtras(extraFields, payload.transfer),
        };
        show.value = true;
    }

    async function submit() {
        errors.value = {};
        form.value.amount = evaluateAmount(form.value.amount);

        const url = isEditing.value
            ? buildPath(updatePath, { transferId: editingTransferId.value })
            : createPath;

        const payload = await request(url, {
            ...form.value,
            description: form.value.description || null,
        });
        if (!payload) return;
        if (payload.success === false) {
            errors.value = payload.errors ?? {};
            return;
        }
        toast.success(
            t(
                isEditing.value
                    ? "personal_finance.transfers.updated"
                    : "personal_finance.transfers.created",
            ),
        );
        show.value = false;
        onSaved?.(payload.transferId);
    }

    return {
        show,
        isEditing,
        editingTransferId,
        form,
        errors,
        loading,
        openCreate,
        openEdit,
        submit,
    };
}
