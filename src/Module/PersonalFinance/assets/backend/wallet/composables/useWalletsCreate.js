import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * @param {object} extraFields - client-extension fields, `{ key: { default: value } }`.
 *   Each key seeds an empty form entry and is spread into the POST body so
 *   the client's overridden DTO + Input factory can hydrate the entity.
 */
function emptyWalletForm(extraFields = {}) {
    const extras = Object.fromEntries(
        Object.keys(extraFields).map((k) => [
            k,
            extraFields[k]?.default ?? null,
        ]),
    );
    return {
        name: "",
        startBalance: "0.00",
        mode: "budget",
        showOnDashboard: true,
        position: 0,
        ...extras,
    };
}

/**
 * Wallet create flow. Pass `extraFields` to allow client extensions to add
 * custom inputs (cf. `entity_extensibility_convention.md` layer 5).
 */
export function useWalletsCreate(
    createPath,
    onCreated,
    { extraFields = {} } = {},
) {
    const { t } = useI18n();
    const { loading: createLoading, request } = useRequest();

    const showCreate = ref(false);
    const createForm = ref(emptyWalletForm(extraFields));
    const createErrors = ref({});

    function openCreate() {
        createForm.value = emptyWalletForm(extraFields);
        createErrors.value = {};
        showCreate.value = true;
    }

    async function submitCreate() {
        createErrors.value = {};
        createForm.value.startBalance = evaluateAmount(
            createForm.value.startBalance,
        );
        const payload = await request(createPath, createForm.value);
        if (!payload) return;
        if (payload.success === false) {
            createErrors.value = payload.errors ?? {};
            return;
        }
        onCreated(payload.wallet);
        toast.success(t("personal_finance.wallets.created"));
        showCreate.value = false;
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
