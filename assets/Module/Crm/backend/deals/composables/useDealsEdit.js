import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { emptyDealForm } from "./useDealsCreate.js";

export function useDealsEdit(
    updatePath,
    reset,
    kanbanColumnsLoaded,
    ensureKanbanColumns,
    options = {},
) {
    const { t } = useI18n();
    const extraFields = options.extraFields ?? {};

    const showEdit = ref(false);
    const editingDeal = ref(null);
    const editForm = ref(emptyDealForm(extraFields));
    const { errors: editErrors, validate, clearErrors, setErrors } = useForm();
    const { loading: editLoading, request } = useApiRequest();

    function openEdit(deal) {
        editingDeal.value = deal;
        const extraValues = Object.fromEntries(
            Object.entries(extraFields).map(([key, def]) => [
                key,
                def.fromEntity ? def.fromEntity(deal) : (deal[key] ?? def.default),
            ]),
        );
        editForm.value = {
            name: deal.name,
            stage: deal.stage,
            value: deal.value ?? "",
            contactId: deal.contact?.id ?? "",
            companyId: deal.company?.id ?? "",
            closingDate: deal.closingDate ?? "",
            notes: deal.notes ?? "",
            ...extraValues,
        };
        clearErrors();
        showEdit.value = true;
    }

    async function submitEdit() {
        if (
            !validate({
                name: () =>
                    required(t("backend.crm.deals.errors.name_required"))(
                        editForm.value.name,
                    ),
            })
        )
            return;
        const url = buildPath(updatePath, { id: editingDeal.value.id });
        const data = await request(url, editForm.value);
        if (!data) return;
        if (data.success) {
            showEdit.value = false;
            toast.success(t("backend.crm.deals.updated"));
            reset();
            if (kanbanColumnsLoaded.value) await ensureKanbanColumns(true);
        } else {
            setErrors(translateServerErrors(t, data.errors));
        }
    }

    return {
        showEdit,
        editingDeal,
        editForm,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
    };
}
