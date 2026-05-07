import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";

export function useServicesEdit(serviceList, createPath, updatePath) {
    const { t } = useI18n();
    const { request } = useApiRequest();

    const editModal = reactive({
        open: false,
        service: null,
        errors: {},
        saving: false,
    });
    const editForm = reactive({ name: "" });

    function openCreate() {
        editModal.service = null;
        editModal.errors = {};
        editForm.name = "";
        editModal.open = true;
    }

    function openEdit(service) {
        editModal.service = service;
        editModal.errors = {};
        editForm.name = service.name;
        editModal.open = true;
    }

    async function submitEdit() {
        editModal.saving = true;
        editModal.errors = {};
        try {
            const isCreate = null === editModal.service;
            const url = isCreate
                ? createPath
                : buildPath(updatePath, { id: editModal.service.id });
            const data = await request(url, { name: editForm.name });
            if (!data?.success) {
                editModal.errors = data?.errors ?? {};
                return;
            }
            if (isCreate) {
                serviceList.value.push(data.service);
                serviceList.value.sort((serviceA, serviceB) =>
                    serviceA.name.localeCompare(serviceB.name),
                );
            } else {
                const index = serviceList.value.findIndex(
                    (service) => service.id === editModal.service.id,
                );
                if (index !== -1) serviceList.value[index] = data.service;
            }
            toast.success(t("shared.common.saved"));
            editModal.open = false;
        } finally {
            editModal.saving = false;
        }
    }

    return { editModal, editForm, openCreate, openEdit, submitEdit };
}
