import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";

const DEFAULT_FORM = {
    name: "",
    description: "",
    color: "#3b82f6",
    timezone: "Europe/Paris",
    visibility: "private",
    ownerId: null,
    agencyId: null,
};

/**
 * @typedef {Object} ExtraField
 * @property {*} default
 * @property {(planning: object) => *} fromEntity
 */

export function usePlanningForm(
    plannings,
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
            ...DEFAULT_FORM,
            ...Object.fromEntries(
                Object.entries(extraFields).map(([key, def]) => [
                    key,
                    def.default,
                ]),
            ),
        }),
        fromEntity: (planning) => ({
            name: planning.name,
            description: planning.description ?? "",
            color: planning.color,
            timezone: planning.timezone,
            visibility: planning.visibility,
            ownerId: planning.owner?.id ?? null,
            agencyId: planning.agency?.id ?? null,
            ...Object.fromEntries(
                Object.entries(extraFields).map(([key, def]) => [
                    key,
                    def.fromEntity(planning),
                ]),
            ),
        }),
        createUrl: () => createPath,
        editUrl: (planning) => buildPath(updatePath, { id: planning.id }),
        onSuccess: ({ data, isCreate }) => {
            if (isCreate) {
                plannings.value.push(data.planning);
                plannings.value.sort((planningA, planningB) =>
                    planningA.name.localeCompare(planningB.name),
                );
            } else {
                const index = plannings.value.findIndex(
                    (planning) => planning.id === data.planning.id,
                );
                if (index !== -1) plannings.value[index] = data.planning;
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
