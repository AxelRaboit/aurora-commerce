import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";

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
    const { request } = useRequest();
    const extraFields = options.extraFields ?? {};

    const editModal = reactive({
        open: false,
        planning: null,
        errors: {},
        saving: false,
    });
    const editForm = reactive({
        ...DEFAULT_FORM,
        ...Object.fromEntries(
            Object.entries(extraFields).map(([key, def]) => [key, def.default]),
        ),
    });

    function resetForm() {
        Object.assign(editForm, DEFAULT_FORM);
        for (const [key, def] of Object.entries(extraFields)) {
            editForm[key] = def.default;
        }
    }

    function loadFrom(planning) {
        Object.assign(editForm, {
            name: planning.name,
            description: planning.description ?? "",
            color: planning.color,
            timezone: planning.timezone,
            visibility: planning.visibility,
            ownerId: planning.owner?.id ?? null,
            agencyId: planning.agency?.id ?? null,
        });
        for (const [key, def] of Object.entries(extraFields)) {
            editForm[key] = def.fromEntity(planning);
        }
    }

    function openCreate() {
        editModal.planning = null;
        editModal.errors = {};
        resetForm();
        editModal.open = true;
    }

    function openEdit(planning) {
        editModal.planning = planning;
        editModal.errors = {};
        loadFrom(planning);
        editModal.open = true;
    }

    async function submit() {
        editModal.saving = true;
        editModal.errors = {};
        try {
            const isCreate = null === editModal.planning;
            const url = isCreate
                ? createPath
                : buildPath(updatePath, { id: editModal.planning.id });
            const data = await request(url, { ...editForm });
            if (!data?.success) {
                editModal.errors = data?.errors ?? {};
                return;
            }
            if (isCreate) {
                plannings.value.push(data.planning);
                plannings.value.sort((planningA, planningB) =>
                    planningA.name.localeCompare(planningB.name),
                );
            } else {
                const index = plannings.value.findIndex(
                    (planning) => planning.id === editModal.planning.id,
                );
                if (index !== -1) plannings.value[index] = data.planning;
            }
            toast.success(t("shared.common.saved"));
            editModal.open = false;
        } finally {
            editModal.saving = false;
        }
    }

    return { editModal, editForm, openCreate, openEdit, submit };
}
