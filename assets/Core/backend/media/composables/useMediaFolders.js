import { ref, reactive, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useMediaFolders(
    props,
    folders,
    currentFolderId,
    flatFolders,
    allFlatFolders,
    navigateTo,
) {
    const { t } = useI18n();

    const folderModal = reactive({
        open: false,
        editing: null,
        errors: {},
        saving: false,
    });
    const folderForm = reactive({ name: "", parentId: null });

    function openCreateFolder() {
        folderModal.editing = null;
        folderModal.errors = {};
        folderForm.name = "";
        folderForm.parentId = currentFolderId.value;
        folderModal.open = true;
    }

    function openEditFolder(folder) {
        folderModal.editing = folder;
        folderModal.errors = {};
        folderForm.name = folder.name;
        folderForm.parentId = folder.parentId;
        folderModal.open = true;
    }

    async function submitFolder() {
        folderModal.saving = true;
        folderModal.errors = {};
        try {
            const url = folderModal.editing
                ? buildPath(props.folderEditPath, {
                      id: folderModal.editing.id,
                  })
                : props.folderCreatePath;
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(folderForm),
            });
            const data = await response.json();
            if (!data.success) {
                folderModal.errors = data.errors ?? {};
                return;
            }
            if (folderModal.editing) {
                const idx = folders.value.findIndex(
                    (f) => f.id === data.folder.id,
                );
                if (idx !== -1) folders.value[idx] = data.folder;
            } else {
                folders.value.push(data.folder);
            }
            toast.success(t("shared.common.saved"));
            folderModal.open = false;
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            folderModal.saving = false;
        }
    }

    const deletingFolder = ref(null);

    async function confirmDeleteFolder() {
        const folder = deletingFolder.value;
        if (!folder) return;
        try {
            const response = await fetch(
                buildPath(props.folderDeletePath, { id: folder.id }),
                { method: HttpMethod.Post },
            );
            const data = await response.json();
            if (!data.success) {
                toast.error(t("shared.common.error"));
                return;
            }
            folders.value = folders.value.filter((f) => f.id !== folder.id);
            if (currentFolderId.value === folder.id) {
                navigateTo(folder.parentId ?? null);
                return;
            }
            toast.success(t("shared.common.deleted"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            deletingFolder.value = null;
        }
    }

    const folderParentOptions = computed(() => {
        if (!folderModal.editing) return flatFolders.value;
        const forbidden = new Set([folderModal.editing.id]);
        const addDescendants = (id) => {
            for (const f of folders.value) {
                if (f.parentId === id && !forbidden.has(f.id)) {
                    forbidden.add(f.id);
                    addDescendants(f.id);
                }
            }
        };
        addDescendants(folderModal.editing.id);
        return allFlatFolders.value.filter((f) => !forbidden.has(f.id));
    });

    function withDepthLabel(list) {
        return list.map((f) => ({
            ...f,
            displayLabel: "— ".repeat(f.depth) + f.name,
        }));
    }

    const folderEditOptions = computed(() =>
        withDepthLabel(allFlatFolders.value),
    );
    const folderParentSelectOptions = computed(() =>
        withDepthLabel(folderParentOptions.value),
    );

    return {
        folderModal,
        folderForm,
        openCreateFolder,
        openEditFolder,
        submitFolder,
        deletingFolder,
        confirmDeleteFolder,
        folderParentOptions,
        folderEditOptions,
        folderParentSelectOptions,
        withDepthLabel,
    };
}
