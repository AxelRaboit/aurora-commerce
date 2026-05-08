import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

/**
 * @typedef {Object} ExtraField
 * @property {*} default - Initial value (used by openCreate / emptyDealForm).
 * @property {(deal: object) => *} [fromEntity] - Reads the field from an existing deal (used by openEdit).
 */

/**
 * @param {Record<string, ExtraField>} extraFields
 * @returns {Object} Empty form initialised with Aurora defaults + extra defaults.
 */
export function emptyDealForm(extraFields = {}) {
    return {
        name: "",
        stage: "lead",
        value: "",
        contactId: "",
        companyId: "",
        closingDate: "",
        notes: "",
        ...Object.fromEntries(
            Object.entries(extraFields).map(([key, def]) => [key, def.default]),
        ),
    };
}

export function useDealsCreate(
    createPath,
    reset,
    kanbanColumnsLoaded,
    ensureKanbanColumns,
    options = {},
) {
    const { t } = useI18n();
    const extraFields = options.extraFields ?? {};

    const showCreate = ref(false);
    const newDeal = ref(emptyDealForm(extraFields));
    const {
        errors: createErrors,
        validate,
        clearErrors,
        setErrors,
    } = useForm();
    const { loading: createLoading, request } = useApiRequest();

    function openCreate() {
        newDeal.value = emptyDealForm(extraFields);
        clearErrors();
        showCreate.value = true;
    }

    async function submitCreate() {
        if (
            !validate({
                name: () =>
                    required(t("backend.crm.deals.errors.name_required"))(
                        newDeal.value.name,
                    ),
            })
        )
            return;
        const data = await request(createPath, newDeal.value);
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("backend.crm.deals.created"));
            reset();
            if (kanbanColumnsLoaded.value) await ensureKanbanColumns(true);
        } else {
            setErrors(translateServerErrors(t, data.errors));
        }
    }

    return {
        showCreate,
        newDeal,
        createErrors,
        createLoading,
        openCreate,
        submitCreate,
    };
}
