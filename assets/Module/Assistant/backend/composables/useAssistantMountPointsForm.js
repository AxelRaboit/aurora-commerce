import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";

/**
 * Create + edit form for AssistantMountPoint entries. Wraps `useFormModal`
 * so the SFC stays presentation-only and we get consistent server-error
 * mapping, loading state and modal lifecycle shared with the rest of the
 * Aurora admin (Agencies, etc.).
 */
export function useAssistantMountPointsForm(
    mountPointList,
    createPath,
    updatePath,
) {
    const { t } = useI18n();

    const {
        modal,
        form,
        errors,
        loading,
        openCreate,
        openEdit,
        close,
        submit,
    } = useFormModal({
        empty: () => ({
            name: "",
            path: "",
            access: "read_only",
            active: true,
        }),
        fromEntity: (mountPoint) => ({
            name: mountPoint.name,
            path: mountPoint.path,
            access: mountPoint.access,
            active: mountPoint.active,
        }),
        createUrl: () => createPath,
        editUrl: (mountPoint) => buildPath(updatePath, { id: mountPoint.id }),
        onSuccess: ({ data, isCreate }) => {
            const mountPoint = data.mountPoint;
            if (isCreate) {
                mountPointList.value.push(mountPoint);
            } else {
                const index = mountPointList.value.findIndex(
                    (m) => m.id === mountPoint.id,
                );
                if (index !== -1) mountPointList.value[index] = mountPoint;
            }

            mountPointList.value.sort((a, b) => a.name.localeCompare(b.name));
            toast.success(t("shared.common.saved"));
        },
    });

    return {
        modal,
        form,
        errors,
        loading,
        openCreate,
        openEdit,
        close,
        submit,
    };
}
