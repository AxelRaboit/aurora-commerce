import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";

/**
 * @typedef {Object} ExtraField
 * @property {*} default - Initial/reset value for this field.
 * @property {(service: object) => *} fromEntity - Reads the field value from an existing service when opening edit.
 */

export function useServicesForm(
    serviceList,
    createPath,
    updatePath,
    options = {},
) {
    const { t } = useI18n();
    const extraFields = options.extraFields ?? {};

    const { modal, form, errors, loading, openCreate, openEdit, close, submit } = useFormModal({
        empty: () => ({
            name: "",
            ...Object.fromEntries(
                Object.entries(extraFields).map(([key, def]) => [key, def.default]),
            ),
        }),
        fromEntity: (service) => ({
            name: service.name,
            ...Object.fromEntries(
                Object.entries(extraFields).map(([key, def]) => [key, def.fromEntity(service)]),
            ),
        }),
        createUrl: () => createPath,
        editUrl:   (service) => buildPath(updatePath, { id: service.id }),
        onSuccess: ({ data, isCreate }) => {
            if (isCreate) {
                serviceList.value.push(data.service);
                serviceList.value.sort((serviceA, serviceB) =>
                    serviceA.name.localeCompare(serviceB.name),
                );
            } else {
                const index = serviceList.value.findIndex(
                    (service) => service.id === data.service.id,
                );
                if (index !== -1) serviceList.value[index] = data.service;
            }
            toast.success(t("shared.common.saved"));
        },
    });

    return { modal, form, errors, loading, openCreate, openEdit, close, submit };
}
