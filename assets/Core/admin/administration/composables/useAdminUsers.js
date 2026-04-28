import { ref, computed } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { useForm } from "@/shared/composables/form/useForm.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { submitForm } from "@/shared/utils/http/formSubmit.js";
import { toast } from "vue-sonner";
import {
    required,
    email,
    compose,
} from "@/shared/utils/validation/validators.js";

const DEFAULT_LOCALE = "fr";

export function useAdminUsers(
    usersPath,
    userCreatePath,
    userUpdatePath,
    userToggleRolePath,
    userDeletePath,
    impersonatePath,
    csrfToken,
    initialUsers,
    initialSearch,
) {
    const { t } = useI18n();

    const usersData = ref(initialUsers ?? { items: [] });
    const parsedUsers = computed(() => usersData.value ?? { items: [] });

    const searchInput = ref(initialSearch);
    const loading = ref(false);

    async function load() {
        loading.value = true;
        try {
            const url = new URL(usersPath, window.location.origin);
            if (searchInput.value)
                url.searchParams.set("search", searchInput.value);
            const response = await fetch(url, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (!response.ok) throw new Error();
            usersData.value = await response.json();
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    function performSearch() {
        load();
    }

    const showCreateModal = ref(false);
    const newUser = ref({
        name: "",
        email: "",
        password: "",
        locale: DEFAULT_LOCALE,
    });
    const {
        errors: createErrors,
        validate: validateCreate,
        setErrors: setCreateErrors,
        clearErrors: clearCreateErrors,
    } = useForm();
    const { loading: createLoading, request: createRequest } = useApiRequest();

    function openCreate() {
        showCreateModal.value = true;
        newUser.value = {
            name: "",
            email: "",
            password: "",
            locale: DEFAULT_LOCALE,
        };
        clearCreateErrors();
    }

    async function submitCreate() {
        const isValid = validateCreate({
            name: () =>
                required(t("admin.profile.errors.name_required"))(
                    newUser.value.name,
                ),
            email: () =>
                compose(
                    required(t("admin.profile.errors.email_invalid")),
                    email(t("admin.profile.errors.email_invalid")),
                )(newUser.value.email),
            password: () => {
                if (
                    !newUser.value.password ||
                    newUser.value.password.length < 8
                )
                    return t("admin.profile.errors.password_too_short");
                return null;
            },
        });

        if (!isValid) return;

        const data = await createRequest(userCreatePath, newUser.value);
        if (!data) return;
        if (data.success) {
            window.location.reload();
        } else {
            setCreateErrors(data.errors ?? {});
        }
    }

    const showEditModal = ref(false);
    const editingUser = ref(null);
    const editUserForm = ref({
        name: "",
        email: "",
        password: "",
        locale: DEFAULT_LOCALE,
    });
    const {
        errors: editErrors,
        validate: validateEdit,
        setErrors: setEditErrors,
        clearErrors: clearEditErrors,
    } = useForm();
    const { loading: editLoading, request: editRequest } = useApiRequest();

    function openEdit(user) {
        editingUser.value = user;
        editUserForm.value = {
            name: user.name,
            email: user.email,
            password: "",
            locale: user.locale ?? DEFAULT_LOCALE,
        };
        clearEditErrors();
        showEditModal.value = true;
    }

    function closeEdit() {
        showEditModal.value = false;
        editingUser.value = null;
    }

    async function submitEdit() {
        if (!editingUser.value) return;

        const isValid = validateEdit({
            name: () =>
                required(t("admin.profile.errors.name_required"))(
                    editUserForm.value.name,
                ),
            email: () =>
                compose(
                    required(t("admin.profile.errors.email_invalid")),
                    email(t("admin.profile.errors.email_invalid")),
                )(editUserForm.value.email),
            password: () => {
                if (
                    editUserForm.value.password &&
                    editUserForm.value.password.length < 8
                )
                    return t("admin.profile.errors.password_too_short");
                return null;
            },
        });

        if (!isValid) return;

        const url = buildPath(userUpdatePath, { id: editingUser.value.id });
        const data = await editRequest(url, editUserForm.value);
        if (!data) return;
        if (data.success) {
            window.location.reload();
        } else {
            setEditErrors(data.errors ?? {});
        }
    }

    const pendingDelete = ref(null);

    function confirmDelete(user) {
        pendingDelete.value = user;
    }

    function doDelete() {
        if (!pendingDelete.value) return;
        const url = buildPath(userDeletePath, { id: pendingDelete.value.id });
        submitForm(url, csrfToken);
        pendingDelete.value = null;
    }

    const pendingToggleRole = ref(null);

    function confirmToggleRole(user) {
        pendingToggleRole.value = user;
    }

    function doToggleRole() {
        if (!pendingToggleRole.value) return;
        const url = userToggleRolePath.replace(
            "__id__",
            pendingToggleRole.value.id,
        );
        submitForm(url, csrfToken);
        pendingToggleRole.value = null;
    }

    return {
        parsedUsers,
        loading,
        load,
        searchInput,
        performSearch,
        showCreateModal,
        newUser,
        createLoading,
        createErrors,
        openCreate,
        submitCreate,
        showEditModal,
        editingUser,
        editUserForm,
        editLoading,
        editErrors,
        openEdit,
        closeEdit,
        submitEdit,
        pendingDelete,
        confirmDelete,
        doDelete,
        pendingToggleRole,
        confirmToggleRole,
        doToggleRole,
        impersonatePath,
    };
}
