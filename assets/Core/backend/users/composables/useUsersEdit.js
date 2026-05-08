import { ref, reactive, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useUsersEdit(props, fetchUsers, options = {}) {
    const { t } = useI18n();
    const extraFields = options.extraFields ?? {};

    const selectableUsers = ref([]);

    async function loadSelectableUsers() {
        if (selectableUsers.value.length) return;
        try {
            const response = await fetch(props.selectablePath);
            const data = await response.json();
            selectableUsers.value = data.success ? data.items : [];
        } catch {
            selectableUsers.value = [];
        }
    }

    const editModal = reactive({
        open: false,
        editing: null,
        errors: {},
        saving: false,
        photoUploading: false,
    });
    const editForm = reactive({
        name: "",
        email: "",
        role: "",
        password: "",
        managerId: null,
        agencyId: null,
        serviceId: null,
        ...Object.fromEntries(
            Object.entries(extraFields).map(([key, def]) => [key, def.default]),
        ),
    });

    const managerOptions = computed(() => {
        const editingId = editModal.editing?.id ?? 0;
        return [
            { value: "", label: "—" },
            ...selectableUsers.value
                .filter((user) => user.id !== editingId)
                .map((user) => ({ value: String(user.id), label: user.name })),
        ];
    });

    const agencyOptions = computed(() => [
        { value: "", label: "—" },
        ...(props.agencies ?? []),
    ]);

    const serviceOptions = computed(() => [
        { value: "", label: "—" },
        ...(props.services ?? []),
    ]);

    function openEdit(user) {
        editModal.editing = user;
        editModal.errors = {};
        editForm.name = user.name;
        editForm.email = user.email;
        editForm.role = user.role ?? props.roles[0]?.value ?? "";
        editForm.password = "";
        editForm.managerId = user.managerId ? String(user.managerId) : "";
        editForm.agencyId = user.agencyId ? String(user.agencyId) : "";
        editForm.serviceId = user.serviceId ? String(user.serviceId) : "";
        for (const [key, def] of Object.entries(extraFields)) {
            editForm[key] = def.fromEntity ? def.fromEntity(user) : (user[key] ?? def.default);
        }
        editModal.open = true;
        loadSelectableUsers();
    }

    async function onPhotoSelected(file) {
        if (!file || !editModal.editing) return;
        editModal.photoUploading = true;
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const url = buildPath(props.photoUploadPath, {
                id: editModal.editing.id,
            });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                body: formData,
            });
            const data = await response.json();
            if (!data.success) {
                const message =
                    data.errors?.photo ?? data.error ?? "shared.common.error";
                toast.error(t(message));
                return;
            }
            editModal.editing = data.user;
            toast.success(t("backend.users.photo.uploaded"));
            fetchUsers();
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            editModal.photoUploading = false;
        }
    }

    async function removePhoto() {
        if (!editModal.editing) return;
        editModal.photoUploading = true;
        try {
            const url = buildPath(props.photoDeletePath, {
                id: editModal.editing.id,
            });
            const response = await fetch(url, { method: HttpMethod.Post });
            const data = await response.json();
            if (!data.success) {
                toast.error(t(data.error ?? "shared.common.error"));
                return;
            }
            editModal.editing = data.user;
            toast.success(t("backend.users.photo.removed"));
            fetchUsers();
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            editModal.photoUploading = false;
        }
    }

    async function submitEdit() {
        if (!editModal.editing) return;
        editModal.saving = true;
        editModal.errors = {};
        try {
            const url = buildPath(props.updatePath, {
                id: editModal.editing.id,
            });
            const payload = {
                ...editForm,
                managerId: editForm.managerId
                    ? Number(editForm.managerId)
                    : null,
                agencyId: editForm.agencyId ? Number(editForm.agencyId) : null,
                serviceId: editForm.serviceId
                    ? Number(editForm.serviceId)
                    : null,
            };
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (!data.success) {
                editModal.errors = data.errors ?? {};
                return;
            }
            toast.success(t("shared.common.saved"));
            editModal.open = false;
            fetchUsers();
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            editModal.saving = false;
        }
    }

    return {
        editModal,
        editForm,
        managerOptions,
        agencyOptions,
        serviceOptions,
        openEdit,
        onPhotoSelected,
        removePhoto,
        submitEdit,
    };
}
