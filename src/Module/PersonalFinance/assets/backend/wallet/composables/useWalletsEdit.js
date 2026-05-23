import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * @param {object} extraFields - client-extension fields, `{ key: { default: value } }`.
 *   When opening edit on an existing wallet, the previously-stored custom
 *   value is read from the serialized payload (falling back to `default`).
 */
export function useWalletsEdit(
    updatePath,
    onUpdated,
    { extraFields = {} } = {},
) {
    const { t } = useI18n();
    const { loading: editLoading, request } = useRequest();
    const extraKeys = Object.keys(extraFields);

    function pickExtras(source) {
        return Object.fromEntries(
            extraKeys.map((k) => [
                k,
                source?.[k] ?? extraFields[k]?.default ?? null,
            ]),
        );
    }

    const showEdit = ref(false);
    const editingWallet = ref(null);
    const editForm = ref({
        name: "",
        startBalance: "0.00",
        mode: "budget",
        showOnDashboard: true,
        position: 0,
        ...pickExtras({}),
    });
    const editErrors = ref({});

    function openEdit(wallet) {
        editingWallet.value = wallet;
        editForm.value = {
            name: wallet.name,
            startBalance: wallet.startBalance,
            mode: wallet.mode,
            showOnDashboard: wallet.showOnDashboard ?? true,
            position: wallet.position ?? 0,
            ...pickExtras(wallet),
        };
        editErrors.value = {};
        showEdit.value = true;
    }

    async function submitEdit() {
        if (!editingWallet.value) return;
        editErrors.value = {};
        editForm.value.startBalance = evaluateAmount(
            editForm.value.startBalance,
        );
        const payload = await request(
            buildPath(updatePath, { id: editingWallet.value.id }),
            editForm.value,
        );
        if (!payload) return;
        if (payload.success === false) {
            editErrors.value = payload.errors ?? {};
            return;
        }
        onUpdated(payload.wallet);
        toast.success(t("personal_finance.wallets.updated"));
        showEdit.value = false;
    }

    return {
        showEdit,
        editingWallet,
        editForm,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
    };
}
