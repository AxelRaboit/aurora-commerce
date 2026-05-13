import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";
import { required } from "@/shared/utils/validation/validators.js";

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

    const { modal, form, errors, loading, openCreate, openEdit, close, submit } = useFormModal({
        empty: () => ({
            ...emptyDealForm(extraFields),
        }),
        fromEntity: (deal) => ({
            name:        deal.name,
            stage:       deal.stage,
            value:       deal.value ?? "",
            contactId:   deal.contact?.id ?? "",
            companyId:   deal.company?.id ?? "",
            closingDate: deal.closingDate ?? "",
            notes:       deal.notes ?? "",
            ...Object.fromEntries(
                Object.entries(extraFields).map(([key, def]) => [
                    key,
                    def.fromEntity ? def.fromEntity(deal) : (deal[key] ?? def.default),
                ]),
            ),
        }),
        createUrl: () => createPath,
        editUrl:   (deal) => buildPath(updatePath, { id: deal.id }),
        rules: () => ({
            name: () => required(t("backend.crm.deals.errors.name_required"))(form.name),
        }),
        onSuccess: async ({ isCreate }) => {
            toast.success(t(isCreate ? "backend.crm.deals.created" : "backend.crm.deals.updated"));
            reset();
            if (kanbanColumnsLoaded.value) await ensureKanbanColumns(true);
        },
    });

    return {
        modal,
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
        name:        "",
        stage:       "lead",
        value:       "",
        contactId:   "",
        companyId:   "",
        closingDate: "",
        notes:       "",
        ...Object.fromEntries(
            Object.entries(extraFields).map(([key, def]) => [key, def.default]),
        ),
    };
}
