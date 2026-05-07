import { ref } from "vue";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

export const LABEL_COLORS = [
    "slate",
    "accent",
    "rose",
    "emerald",
    "amber",
    "sky",
    "violet",
];

export function useLabelsManage(paths, activeProject, reloadDetail) {
    const { t } = useI18n();

    const showLabelsModal = ref(false);
    const editingLabel = ref(null);
    const labelForm = ref({ name: "", color: "accent" });

    const { errors: labelErrors, validate, clearErrors, setErrors } = useForm();
    const { loading, request } = useApiRequest();

    function openLabelsModal() {
        editingLabel.value = null;
        labelForm.value = { name: "", color: "accent" };
        clearErrors();
        showLabelsModal.value = true;
    }

    function startEdit(label) {
        editingLabel.value = label;
        labelForm.value = { name: label.name, color: label.color };
        clearErrors();
    }

    function cancelEdit() {
        editingLabel.value = null;
        labelForm.value = { name: "", color: "accent" };
        clearErrors();
    }

    async function submitLabel() {
        if (!activeProject.value) return;
        if (
            !validate({
                name: () =>
                    required(t("backend.projects.errors.label_name_required"))(
                        labelForm.value.name,
                    ),
            })
        )
            return;

        const url = editingLabel.value
            ? buildPath(paths.update, { labelId: editingLabel.value.id })
            : buildPath(paths.create, { id: activeProject.value.id });
        const data = await request(url, labelForm.value);
        if (!data) return;
        if (data.success) {
            cancelEdit();
            await reloadDetail();
        } else {
            setErrors(translateServerErrors(t, data.errors));
        }
    }

    async function deleteLabel(label) {
        if (
            !confirm(
                t("backend.projects.errors.label_delete_confirm", {
                    name: label.name,
                }),
            )
        )
            return;
        const url = buildPath(paths.delete, { labelId: label.id });
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
            });
            if (!response.ok) throw new Error();
            await reloadDetail();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    return {
        showLabelsModal,
        editingLabel,
        labelForm,
        labelErrors,
        loading,
        openLabelsModal,
        startEdit,
        cancelEdit,
        submitLabel,
        deleteLabel,
    };
}
