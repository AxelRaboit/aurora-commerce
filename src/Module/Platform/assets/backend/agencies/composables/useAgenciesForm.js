import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";

/**
 * @typedef {Object} ExtraField
 * @property {*} default - Initial/reset value for this field.
 * @property {(agency: object) => *} fromEntity - Reads the field value from an existing agency when opening edit.
 */

export function useAgenciesForm(
    agencyList,
    createPath,
    updatePath,
    options = {},
) {
    const { t } = useI18n();
    const extraFields = options.extraFields ?? {};

    const {
        modal,
        form,
        errors,
        loading,
        openCreate,
        openEdit,
        close,
        submit,
    } = useFormModal({
        empty: () => ({
            name: "",
            ...Object.fromEntries(
                Object.entries(extraFields).map(([key, def]) => [
                    key,
                    def.default,
                ]),
            ),
        }),
        fromEntity: (agency) => ({
            name: agency.name,
            ...Object.fromEntries(
                Object.entries(extraFields).map(([key, def]) => [
                    key,
                    def.fromEntity(agency),
                ]),
            ),
        }),
        createUrl: () => createPath,
        editUrl: (agency) => buildPath(updatePath, { id: agency.id }),
        onSuccess: ({ data, isCreate }) => {
            if (isCreate) {
                agencyList.value.push(data.agency);
                agencyList.value.sort((agencyA, agencyB) =>
                    agencyA.name.localeCompare(agencyB.name),
                );
            } else {
                const index = agencyList.value.findIndex(
                    (agency) => agency.id === data.agency.id,
                );
                if (index !== -1) agencyList.value[index] = data.agency;
            }
            toast.success(t("shared.common.saved"));
        },
    });

    return {
        modal,
        form,
        errors,
        loading,
        openCreate,
        openEdit,
        close,
        submit,
    };
}
