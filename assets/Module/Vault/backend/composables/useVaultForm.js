import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useApiRequest } from "@shared/composables/api/useApiRequest.js";
import { useForm } from "@shared/composables/form/useForm.js";
import { required } from "@shared/utils/validation/validators.js";
import { buildPath } from "@shared/utils/http/buildPath.js";
import { translateServerErrors } from "@shared/utils/validation/translateServerErrors.js";
import { emptyFieldsForType } from "@vault/backend/utils/recordTypes.js";

export function useVaultForm(crypto, props, onSuccess) {
    const { t } = useI18n();

    const showCreate = ref(false);
    const showEdit = ref(false);
    const editingEntry = ref(null);

    const createForm = ref({
        type: "login",
        title: "",
        url: "",
        isFavorite: false,
        folderId: null,
        fields: emptyFieldsForType("login"),
    });
    const editForm = ref({
        type: "login",
        title: "",
        url: "",
        isFavorite: false,
        folderId: null,
        fields: {},
    });

    const {
        errors: createErrors,
        validate: validateCreate,
        clearErrors: clearCreate,
        setErrors: setCreateErrors,
    } = useForm();
    const {
        errors: editErrors,
        validate: validateEdit,
        clearErrors: clearEdit,
        setErrors: setEditErrors,
    } = useForm();
    const { loading: createLoading, request: createRequest } = useApiRequest();
    const { loading: editLoading, request: editRequest } = useApiRequest();

    function openCreate() {
        createForm.value = {
            type: "login",
            title: "",
            url: "",
            isFavorite: false,
            folderId: null,
            fields: emptyFieldsForType("login"),
        };
        clearCreate();
        showCreate.value = true;
    }

    function onCreateTypeChange(type) {
        createForm.value.type = type;
        createForm.value.fields = emptyFieldsForType(type);
    }

    function openEdit(entry, decryptedFields) {
        editingEntry.value = entry;
        editForm.value = {
            type: entry.type,
            title: entry.title,
            url: entry.url ?? "",
            isFavorite: entry.isFavorite,
            folderId: entry.folderId ?? null,
            fields: { ...decryptedFields },
        };
        clearEdit();
        showEdit.value = true;
    }

    function onEditTypeChange(type) {
        editForm.value.type = type;
        editForm.value.fields = emptyFieldsForType(type);
    }

    async function submitCreate() {
        if (
            !validateCreate({
                title: () =>
                    required(t("vault.entries.errors.title_required"))(
                        createForm.value.title,
                    ),
            })
        )
            return;

        const { encryptedData, iv } = await crypto.encrypt(
            createForm.value.fields,
        );

        const data = await createRequest(props.createEntryPath, {
            type: createForm.value.type,
            title: createForm.value.title.trim(),
            url: createForm.value.url.trim() || null,
            isFavorite: createForm.value.isFavorite,
            folderId: createForm.value.folderId,
            encryptedData,
            iv,
        });

        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            onSuccess("created", data.entry);
        } else {
            setCreateErrors(translateServerErrors(t, data.errors));
        }
    }

    async function submitEdit() {
        if (
            !validateEdit({
                title: () =>
                    required(t("vault.entries.errors.title_required"))(
                        editForm.value.title,
                    ),
            })
        )
            return;

        const { encryptedData, iv } = await crypto.encrypt(
            editForm.value.fields,
        );
        const url = buildPath(props.updateEntryPath, {
            id: editingEntry.value.id,
        });

        const data = await editRequest(url, {
            type: editForm.value.type,
            title: editForm.value.title.trim(),
            url: editForm.value.url.trim() || null,
            isFavorite: editForm.value.isFavorite,
            folderId: editForm.value.folderId,
            encryptedData,
            iv,
        });

        if (!data) return;
        if (data.success) {
            showEdit.value = false;
            onSuccess("updated", data.entry);
        } else {
            setEditErrors(translateServerErrors(t, data.errors));
        }
    }

    return {
        showCreate,
        showEdit,
        editingEntry,
        createForm,
        editForm,
        createErrors,
        editErrors,
        createLoading,
        editLoading,
        openCreate,
        openEdit,
        onCreateTypeChange,
        onEditTypeChange,
        submitCreate,
        submitEdit,
    };
}
