import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";

/**
 * @typedef {Object} ExtraField
 * @property {*} default - Initial/reset value for this field.
 * @property {(agency: object) => *} fromEntity - Reads the field value from an existing agency when opening edit.
 */

export function useAgenciesForm(agencyList, createPath, updatePath, options = {}) {
    const { t } = useI18n();
    const { request } = useApiRequest();

    const extraFields = options.extraFields ?? {};

    const editModal = reactive({
        open: false,
        agency: null,
        errors: {},
        saving: false,
    });
    const editForm = reactive({
        name: "",
        ...Object.fromEntries(
            Object.entries(extraFields).map(([key, def]) => [key, def.default]),
        ),
    });

    function resetExtras() {
        for (const [key, def] of Object.entries(extraFields)) {
            editForm[key] = def.default;
        }
    }

    function loadExtrasFrom(agency) {
        for (const [key, def] of Object.entries(extraFields)) {
            editForm[key] = def.fromEntity(agency);
        }
    }

    function openCreate() {
        editModal.agency = null;
        editModal.errors = {};
        editForm.name = "";
        resetExtras();
        editModal.open = true;
    }

    function openEdit(agency) {
        editModal.agency = agency;
        editModal.errors = {};
        editForm.name = agency.name;
        loadExtrasFrom(agency);
        editModal.open = true;
    }

    async function submitEdit() {
        editModal.saving = true;
        editModal.errors = {};
        try {
            const isCreate = null === editModal.agency;
            const url = isCreate
                ? createPath
                : buildPath(updatePath, { id: editModal.agency.id });
            const data = await request(url, { ...editForm });
            if (!data?.success) {
                editModal.errors = data?.errors ?? {};
                return;
            }
            if (isCreate) {
                agencyList.value.push(data.agency);
                agencyList.value.sort((agencyA, agencyB) =>
                    agencyA.name.localeCompare(agencyB.name),
                );
            } else {
                const index = agencyList.value.findIndex(
                    (agency) => agency.id === editModal.agency.id,
                );
                if (index !== -1) agencyList.value[index] = data.agency;
            }
            toast.success(t("shared.common.saved"));
            editModal.open = false;
        } finally {
            editModal.saving = false;
        }
    }

    return { editModal, editForm, openCreate, openEdit, submitEdit };
}
