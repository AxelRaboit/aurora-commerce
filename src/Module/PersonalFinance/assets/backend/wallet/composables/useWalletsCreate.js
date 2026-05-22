import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";

export function emptyWalletForm() {
    return {
        name: "",
        startBalance: "0.00",
        mode: "budget",
        showOnDashboard: true,
        position: 0,
    };
}

/**
 * Wallet create flow — owns the modal visibility, form state, errors,
 * loading, and submit pipeline (including amount evaluation before POST).
 */
export function useWalletsCreate(createPath, onCreated) {
    const { t } = useI18n();

    const showCreate = ref(false);
    const createForm = ref(emptyWalletForm());
    const createErrors = ref({});
    const createLoading = ref(false);

    function openCreate() {
        createForm.value = emptyWalletForm();
        createErrors.value = {};
        showCreate.value = true;
    }

    async function submitCreate() {
        if (createLoading.value) return;
        createLoading.value = true;
        createErrors.value = {};
        createForm.value.startBalance = evaluateAmount(
            createForm.value.startBalance,
        );
        try {
            const response = await fetch(createPath, {
                method: HttpMethod.Post,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(createForm.value),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                createErrors.value = payload?.errors ?? {};
                return;
            }
            onCreated(payload.wallet);
            toast.success(t("personal_finance.wallets.created"));
            showCreate.value = false;
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            createLoading.value = false;
        }
    }

    return {
        showCreate,
        createForm,
        createErrors,
        createLoading,
        openCreate,
        submitCreate,
    };
}
