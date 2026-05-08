import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

/**
 * @typedef {Object} ExtraField
 * @property {*} default - Initial/reset value for this field.
 * @property {(deal: object) => *} [fromEntity] - Reads the field value from an existing deal when opening edit.
 */

/**
 * Unified create + edit composable for deals — matches the Aurora convention
 * (single composable, single modal, single `extra-form-fields` slot).
 *
 * @param {string} createPath
 * @param {string} updatePath
 * @param {Function} reset - Reload the deal list.
 * @param {import('vue').Ref<boolean>} kanbanColumnsLoaded
 * @param {Function} ensureKanbanColumns
 * @param {{ extraFields?: Record<string, ExtraField> }} [options]
 */
export function useDealsForm(
    createPath,
    updatePath,
    reset,
    kanbanColumnsLoaded,
    ensureKanbanColumns,
    options = {},
) {
    const { t } = useI18n();
    const extraFields = options.extraFields ?? {};

    const formModal = reactive({
        open: false,
        deal: null,
    });

    const form = reactive(emptyDealForm(extraFields));

    const { errors, validate, clearErrors, setErrors } = useForm();
    const { loading, request } = useApiRequest();

    function resetForm() {
        Object.assign(form, emptyDealForm(extraFields));
    }

    function loadFrom(deal) {
        form.name = deal.name;
        form.stage = deal.stage;
        form.value = deal.value ?? "";
        form.contactId = deal.contact?.id ?? "";
        form.companyId = deal.company?.id ?? "";
        form.closingDate = deal.closingDate ?? "";
        form.notes = deal.notes ?? "";
        for (const [key, def] of Object.entries(extraFields)) {
            form[key] = def.fromEntity ? def.fromEntity(deal) : (deal[key] ?? def.default);
        }
    }

    function openCreate() {
        formModal.deal = null;
        resetForm();
        clearErrors();
        formModal.open = true;
    }

    function openEdit(deal) {
        formModal.deal = deal;
        loadFrom(deal);
        clearErrors();
        formModal.open = true;
    }

    async function submit() {
        if (
            !validate({
                name: () =>
                    required(t("backend.crm.deals.errors.name_required"))(form.name),
            })
        )
            return;

        const isCreate = null === formModal.deal;
        const url = isCreate
            ? createPath
            : buildPath(updatePath, { id: formModal.deal.id });
        const data = await request(url, { ...form });
        if (!data) return;

        if (data.success) {
            formModal.open = false;
            toast.success(t(isCreate ? "backend.crm.deals.created" : "backend.crm.deals.updated"));
            reset();
            if (kanbanColumnsLoaded.value) await ensureKanbanColumns(true);
        } else {
            setErrors(translateServerErrors(t, data.errors));
        }
    }

    return {
        formModal,
        form,
        errors,
        loading,
        openCreate,
        openEdit,
        submit,
    };
}

/**
 * @param {Record<string, ExtraField>} extraFields
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
