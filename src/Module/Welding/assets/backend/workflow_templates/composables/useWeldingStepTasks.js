import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

/**
 * Checklist task CRUD on a workflow step (admin design side).
 * Mutates the parent's `steps` array — each step has a `tasks` collection.
 */
export function useWeldingStepTasks(steps) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    // ── Add / edit modal ──────────────────────────────────────────────────
    const modalStep = ref(null);
    const editing = ref(null);
    const form = ref({});
    const errors = ref({});

    function openCreate(step) {
        modalStep.value = step;
        editing.value = null;
        form.value = {
            label: "",
            description: "",
            position: step.tasks.length,
            required: true,
        };
        errors.value = {};
    }

    function openEdit(step, task) {
        modalStep.value = step;
        editing.value = task;
        form.value = {
            label: task.label,
            description: task.description ?? "",
            position: task.position,
            required: task.required,
        };
        errors.value = {};
    }

    function close() {
        modalStep.value = null;
        editing.value = null;
    }

    async function save() {
        errors.value = {};

        let data;
        if (editing.value) {
            data = await request(
                `/backend/welding/workflow-step-task-templates/${editing.value.id}/edit`,
                form.value,
            );
        } else {
            data = await request(
                "/backend/welding/workflow-step-task-templates",
                {
                    ...form.value,
                    workflowStepTemplateId: modalStep.value.id,
                },
            );
        }
        if (!data) return;
        if (data.success) {
            const stepIdx = steps.value.findIndex(
                (s) => s.id === modalStep.value.id,
            );
            if (editing.value) {
                const taskIdx = steps.value[stepIdx].tasks.findIndex(
                    (t) => t.id === editing.value.id,
                );
                steps.value[stepIdx].tasks[taskIdx] = data.entry;
            } else {
                steps.value[stepIdx].tasks.push(data.entry);
            }
            toast.success(
                t(
                    editing.value
                        ? "welding.editor.task_updated"
                        : "welding.editor.task_added",
                ),
            );
            close();
        } else if (data.errors) {
            errors.value = translateServerErrors(t, data.errors);
        }
    }

    // ── Delete confirmation ───────────────────────────────────────────────
    const pendingDelete = ref(null);
    const pendingDeleteStep = ref(null);

    function confirmDelete(step, task) {
        pendingDelete.value = task;
        pendingDeleteStep.value = step;
    }

    async function doDelete() {
        if (!pendingDelete.value) return;
        const task = pendingDelete.value;
        const step = pendingDeleteStep.value;
        const data = await request(
            `/backend/welding/workflow-step-task-templates/${task.id}/delete`,
            {},
        );
        if (data?.success) {
            const stepIdx = steps.value.findIndex((s) => s.id === step.id);
            steps.value[stepIdx].tasks = steps.value[stepIdx].tasks.filter(
                (t) => t.id !== task.id,
            );
            toast.success(t("welding.editor.task_deleted"));
            pendingDelete.value = null;
            pendingDeleteStep.value = null;
        }
    }

    return {
        loading,
        // Modal
        modalStep,
        editing,
        form,
        errors,
        openCreate,
        openEdit,
        close,
        save,
        // Delete
        pendingDelete,
        confirmDelete,
        doDelete,
    };
}
