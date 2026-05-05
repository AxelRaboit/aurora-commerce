import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";

export function useAgenciesEdit(agencyList, createPath, updatePath) {
    const { t } = useI18n();
    const { request } = useApiRequest();

    const editModal = reactive({
        open: false,
        agency: null,
        errors: {},
        saving: false,
    });
    const editForm = reactive({ name: "" });

    function openCreate() {
        editModal.agency = null;
        editModal.errors = {};
        editForm.name = "";
        editModal.open = true;
    }

    function openEdit(agency) {
        editModal.agency = agency;
        editModal.errors = {};
        editForm.name = agency.name;
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
            const data = await request(url, { name: editForm.name });
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
