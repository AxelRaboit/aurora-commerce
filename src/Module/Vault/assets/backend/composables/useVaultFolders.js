import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormModal } from "@shared/composables/form/useFormModal.js";
import { useDelete } from "@shared/composables/form/useDelete.js";
import { required } from "@shared/utils/validation/validators.js";
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

    const {
        modal: folderModal,
        form: folderForm,
        errors: folderErrors,
        loading: folderSaving,
        openCreate: openCreateFolder,
        openEdit: openEditFolder,
        submit: submitFolder,
    } = useFormModal({
        empty: () => ({
            name: "",
            useColor: false,
            color: "#6366f1",
            parentId: currentFolderId.value,
        }),
        fromEntity: (folder) => ({
            name: folder.name,
            useColor: folder.color !== null,
            color: folder.color ?? "#6366f1",
            parentId: folder.parentId ?? null,
        }),
        createUrl: () => props.createFolderPath,
        editUrl: (folder) =>
            buildPath(props.updateFolderPath, { id: folder.id }),
        buildBody: (form) => ({
            name: form.name.trim(),
            color: form.useColor ? form.color : null,
            position: folderModal.entity
                ? folderModal.entity.position
                : folders.value.filter(
                      (f) => (f.parentId ?? null) === (form.parentId ?? null),
                  ).length,
            parentId: form.parentId,
        }),
        rules: () => ({
            name: () =>
                required(t("vault.folders.errors.name_required"))(
                    folderForm.name,
                ),
        }),
        onSuccess: ({ data, isCreate }) => {
            if (isCreate) {
                folders.value.push(data.folder);
            } else {
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
            }
            toast.success(
                t(isCreate ? "vault.folders.created" : "vault.folders.updated"),
            );
        },
    });

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
        if (folderModal.entity) {
            const desc = getDescendantIds(folders.value, folderModal.entity.id);
            desc.forEach((id) => forbidden.add(id));
        }
        const eligible = folderModal.entity
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
