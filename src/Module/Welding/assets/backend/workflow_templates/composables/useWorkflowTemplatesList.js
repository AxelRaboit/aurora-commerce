import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

/**
 * Workflow templates listing page interactions:
 *   - Create modal (title / description / scope) → redirects to the editor on success
 *   - Per-row Publish / Clone / Archive / Delete — each gated by its own confirmation
 *
 * `items` is the parent's reactive array of templates (mutated in-place on
 * update / delete to avoid a refetch).
 */
export function useWorkflowTemplatesList(items) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    // ── Create modal ──────────────────────────────────────────────────────
    const createOpen = ref(false);
    const form = ref({ title: "", description: "", applicableTo: "" });
    const errors = ref({});

    function openCreate() {
        form.value = { title: "", description: "", applicableTo: "" };
        errors.value = {};
        createOpen.value = true;
    }

    async function submitCreate() {
        errors.value = {};
        const data = await request(
            "/backend/welding/workflow-templates",
            form.value,
        );
        if (!data) return;
        if (data.success) {
            toast.success(t("welding.workflow_templates.created"));
            window.location.href = `/backend/welding/workflow-templates/${data.workflowTemplate.id}/editor`;
            return;
        }
        if (data.errors) errors.value = translateServerErrors(t, data.errors);
    }

    // ── Transitional actions, each gated by a confirm modal ───────────────
    function updateLocal(updated) {
        const idx = items.value.findIndex((x) => x.id === updated.id);
        if (idx !== -1) items.value[idx] = { ...items.value[idx], ...updated };
    }

    const pendingPublish = ref(null);
    const pendingArchive = ref(null);
    const pendingClone = ref(null);
    const pendingDelete = ref(null);

    async function doPublish() {
        if (!pendingPublish.value) return;
        const target = pendingPublish.value;
        const data = await request(
            `/backend/welding/workflow-templates/${target.id}/publish`,
            {},
        );
        if (data?.success) {
            updateLocal(data.workflowTemplate);
            toast.success(t("welding.workflow_templates.published"));
            pendingPublish.value = null;
        }
    }

    async function doArchive() {
        if (!pendingArchive.value) return;
        const target = pendingArchive.value;
        const data = await request(
            `/backend/welding/workflow-templates/${target.id}/archive`,
            {},
        );
        if (data?.success) {
            updateLocal(data.workflowTemplate);
            toast.success(t("welding.workflow_templates.archived"));
            pendingArchive.value = null;
        }
    }

    async function doClone() {
        if (!pendingClone.value) return;
        const target = pendingClone.value;
        const data = await request(
            `/backend/welding/workflow-templates/${target.id}/clone`,
            {},
        );
        if (data?.success) {
            toast.success(t("welding.workflow_templates.cloned"));
            window.location.href = `/backend/welding/workflow-templates/${data.workflowTemplate.id}/editor`;
        }
    }

    async function doDelete() {
        if (!pendingDelete.value) return;
        const target = pendingDelete.value;
        const data = await request(
            `/backend/welding/workflow-templates/${target.id}/delete`,
            {},
        );
        if (data?.success) {
            items.value = items.value.filter((x) => x.id !== target.id);
            toast.success(t("welding.workflow_templates.deleted"));
            pendingDelete.value = null;
        }
    }

    return {
        loading,
        // Create
        createOpen,
        form,
        errors,
        openCreate,
        submitCreate,
        // Confirmations
        pendingPublish,
        doPublish,
        pendingClone,
        doClone,
        pendingArchive,
        doArchive,
        pendingDelete,
        doDelete,
    };
}
