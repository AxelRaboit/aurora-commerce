import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

/**
 * Header-level actions on a workflow template inside the editor:
 *   - Edit modal (title / description / scope) with server-side validation
 *   - Publish / Clone / Archive — each gated by its own confirmation modal
 *
 * `tpl` is the parent's ref to the current template; mutating it here
 * propagates back to the page (status badge, header re-render).
 */
export function useWeldingTemplateEdit(tpl) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    // ── Edit modal ────────────────────────────────────────────────────────
    const editing = ref(false);
    const form = ref({});
    const errors = ref({});

    function openEdit() {
        form.value = {
            title: tpl.value.title,
            description: tpl.value.description ?? "",
            applicableTo: tpl.value.applicableTo ?? "",
        };
        errors.value = {};
        editing.value = true;
    }

    async function submitEdit() {
        const data = await request(
            `/backend/welding/workflow-templates/${tpl.value.id}/edit`,
            form.value,
        );
        if (!data) return;
        if (data.success) {
            Object.assign(tpl.value, data.workflowTemplate);
            editing.value = false;
            toast.success(t("welding.editor.template_updated"));
        } else if (data.errors) {
            errors.value = translateServerErrors(t, data.errors);
        }
    }

    // ── Transitional actions (each gated by a confirm modal) ──────────────
    const showPublishConfirm = ref(false);
    const showCloneConfirm = ref(false);
    const showArchiveConfirm = ref(false);

    async function doPublish() {
        const data = await request(
            `/backend/welding/workflow-templates/${tpl.value.id}/publish`,
            {},
        );
        if (data?.success) {
            tpl.value.status = "published";
            toast.success(t("welding.workflow_templates.published"));
            showPublishConfirm.value = false;
        }
    }

    async function doClone() {
        const data = await request(
            `/backend/welding/workflow-templates/${tpl.value.id}/clone`,
            {},
        );
        if (data?.success) {
            toast.success(t("welding.workflow_templates.cloned"));
            window.location.href = `/backend/welding/workflow-templates/${data.workflowTemplate.id}/editor`;
        }
    }

    async function doArchive() {
        const data = await request(
            `/backend/welding/workflow-templates/${tpl.value.id}/archive`,
            {},
        );
        if (data?.success) {
            tpl.value.status = "archived";
            toast.success(t("welding.workflow_templates.archived"));
            showArchiveConfirm.value = false;
        }
    }

    return {
        loading,
        // Edit modal
        editing,
        form,
        errors,
        openEdit,
        submitEdit,
        // Publish
        showPublishConfirm,
        doPublish,
        // Clone
        showCloneConfirm,
        doClone,
        // Archive
        showArchiveConfirm,
        doArchive,
    };
}
