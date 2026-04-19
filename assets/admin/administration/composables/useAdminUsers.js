import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useForm } from "@/composables/useForm.js";
import { submitForm } from "@/utils/formSubmit.js";
import { required, email, compose } from "@/utils/validators.js";

export function useAdminUsers(usersPath, userCreatePath, userDeletePath, impersonatePath, csrfToken, initialUsers, initialSearch) {
    const { t: translate } = useI18n();

    const parsedUsers = computed(() => { try { return JSON.parse(initialUsers); } catch { return { items: [] }; } });

    const searchInput = ref(initialSearch);

    function performSearch() {
        const url = new URL(usersPath, window.location.origin);
        if (searchInput.value) url.searchParams.set("search", searchInput.value);
        window.location.href = url.toString();
    }

    const showCreateModal = ref(false);
    const newUser = ref({ name: "", email: "", password: "" });
    const createLoading = ref(false);
    const { errors: createErrors, validate: validateCreate, setErrors: setCreateErrors, clearErrors: clearCreateErrors } = useForm();

    function openCreate() {
        showCreateModal.value = true;
        newUser.value = { name: "", email: "", password: "" };
        clearCreateErrors();
    }

    async function submitCreate() {
        const isValid = validateCreate({
            name: () => required(translate("profile.errors.name_required"))(newUser.value.name),
            email: () => compose(
                required(translate("profile.errors.email_invalid")),
                email(translate("profile.errors.email_invalid")),
            )(newUser.value.email),
            password: () => {
                if (!newUser.value.password || newUser.value.password.length < 8) return translate("profile.errors.password_too_short");
                return null;
            },
        });

        if (!isValid) return;

        createLoading.value = true;
        try {
            const response = await fetch(userCreatePath, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(newUser.value),
            });
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                setCreateErrors(data.errors ?? {});
            }
        } finally {
            createLoading.value = false;
        }
    }

    const pendingDelete = ref(null);

    function confirmDelete(user) {
        pendingDelete.value = user;
    }

    function doDelete() {
        if (!pendingDelete.value) return;
        const url = userDeletePath.replace("__id__", pendingDelete.value.id);
        submitForm(url, csrfToken);
        pendingDelete.value = null;
    }

    return {
        parsedUsers,
        searchInput,
        performSearch,
        showCreateModal,
        newUser,
        createLoading,
        createErrors,
        openCreate,
        submitCreate,
        pendingDelete,
        confirmDelete,
        doDelete,
        impersonatePath,
    };
}
