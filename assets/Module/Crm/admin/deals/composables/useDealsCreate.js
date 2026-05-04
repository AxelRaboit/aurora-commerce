import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

export function emptyDealForm() {
    return {
        name: "",
        stage: "lead",
        value: "",
        contactId: "",
        companyId: "",
        closingDate: "",
        notes: "",
    };
}

export function useDealsCreate(
    createPath,
    reset,
    kanbanColumnsLoaded,
    ensureKanbanColumns,
) {
    const { t } = useI18n();

    const showCreate = ref(false);
    const newDeal = ref(emptyDealForm());
    const {
        errors: createErrors,
        validate,
        clearErrors,
        setErrors,
    } = useForm();
    const { loading: createLoading, request } = useApiRequest();

    function openCreate() {
        newDeal.value = emptyDealForm();
        clearErrors();
        showCreate.value = true;
    }

    async function submitCreate() {
        if (
            !validate({
                name: () =>
                    required(t("admin.crm.deals.errors.name_required"))(
                        newDeal.value.name,
                    ),
            })
        )
            return;
        const data = await request(createPath, newDeal.value);
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("admin.crm.deals.created"));
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
