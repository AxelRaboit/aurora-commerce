import { ref, reactive, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@shared/composables/http/useRequest.js";
import { useForm } from "@shared/composables/form/useForm.js";
import { useDelete } from "@shared/composables/form/useDelete.js";
import { required } from "@shared/utils/validation/validators.js";
import { translateServerErrors } from "@shared/utils/validation/translateServerErrors.js";
import { buildPath } from "@shared/utils/http/buildPath.js";
import { getDescendantIds } from "@vault/backend/composables/useVaultTree.js";

export function useVaultFolders(
    props,
    folders,
    currentFolderId,
    entries,
    navigateTo,
) {
    const { t } = useI18n();

    const folderModal = reactive({ open: false, editing: null });
    const folderForm = reactive({
        name: "",
        useColor: false,
        color: "#6366f1",
        parentId: null,
    });

    const {
        errors: folderErrors,
        validate,
        clearErrors,
        setErrors,
    } = useForm();
    const { loading: folderSaving, request: folderRequest } = useRequest();

    function openCreateFolder() {
        folderModal.editing = null;
        folderForm.name = "";
        folderForm.useColor = false;
        folderForm.color = "#6366f1";
        folderForm.parentId = currentFolderId.value;
        clearErrors();
        folderModal.open = true;
    }

    function openEditFolder(folder) {
        folderModal.editing = folder;
        folderForm.name = folder.name;
        folderForm.useColor = folder.color !== null;
        folderForm.color = folder.color ?? "#6366f1";
        folderForm.parentId = folder.parentId ?? null;
        clearErrors();
        folderModal.open = true;
    }

    async function submitFolder() {
        if (
            !validate({
                name: () =>
                    required(t("vault.folders.errors.name_required"))(
                        folderForm.name,
                    ),
            })
        )
            return;

        const isEdit = !!folderModal.editing;
        const url = isEdit
            ? buildPath(props.updateFolderPath, { id: folderModal.editing.id })
            : props.createFolderPath;

        const payload = {
            name: folderForm.name.trim(),
            color: folderForm.useColor ? folderForm.color : null,
            position: isEdit
                ? folderModal.editing.position
                : folders.value.filter(
                      (f) =>
                          (f.parentId ?? null) ===
                          (folderForm.parentId ?? null),
                  ).length,
            parentId: folderForm.parentId,
        };

        const data = await folderRequest(url, payload);
        if (!data) return;

        if (data.success) {
            if (isEdit) {
                const idx = folders.value.findIndex(
                    (f) => f.id === data.folder.id,
                );
                if (idx !== -1) folders.value[idx] = data.folder;
                entries.value.forEach((entry) => {
                    if (entry.folderId === data.folder.id) {
                        entry.folderName = data.folder.name;
                        entry.folderColor = data.folder.color;
                    }
                });
            } else {
                folders.value.push(data.folder);
            }
            toast.success(
                t(isEdit ? "vault.folders.updated" : "vault.folders.created"),
            );
            folderModal.open = false;
        } else {
            setErrors(translateServerErrors(t, data.errors));
        }
    }

    const {
        pendingDelete,
        loading: deleteLoading,
        confirm: confirmDeleteFolder,
        submit: doDeleteFolder,
    } = useDelete(
        props.deleteFolderPath,
        (deletedId) => {
            const folder = folders.value.find((f) => f.id === deletedId);
            const parentId = folder?.parentId ?? null;

            folders.value = folders.value
                .filter((f) => f.id !== deletedId)
                .map((f) =>
                    f.parentId === deletedId ? { ...f, parentId: null } : f,
                );
            entries.value.forEach((entry) => {
                if (entry.folderId === deletedId) {
                    entry.folderId = null;
                    entry.folderName = null;
                    entry.folderColor = null;
                }
            });
            if (currentFolderId.value === deletedId) {
                navigateTo(parentId);
            }
        },
        "vault.folders.deleted",
    );

    const folderParentSelectOptions = computed(() => {
        const forbidden = new Set();
        if (folderModal.editing) {
            const desc = getDescendantIds(
                folders.value,
                folderModal.editing.id,
            );
            desc.forEach((id) => forbidden.add(id));
        }
        const eligible = folderModal.editing
            ? folders.value.filter((f) => !forbidden.has(f.id))
            : folders.value;

        const withDepth = (list, parentId = null, depth = 0) => {
            const children = list.filter(
                (f) => (f.parentId ?? null) === parentId,
            );
            return children.flatMap((f) => [
                { value: f.id, label: "— ".repeat(depth) + f.name },
                ...withDepth(list, f.id, depth + 1),
            ]);
        };

        return [
            { value: null, label: "— " + t("vault.folders.noParent") },
            ...withDepth(eligible),
        ];
    });

    return {
        folderModal,
        folderForm,
        folderErrors,
        folderSaving,
        openCreateFolder,
        openEditFolder,
        submitFolder,
        pendingDelete,
        deleteLoading,
        confirmDeleteFolder,
        doDeleteFolder,
        folderParentSelectOptions,
    };
}
