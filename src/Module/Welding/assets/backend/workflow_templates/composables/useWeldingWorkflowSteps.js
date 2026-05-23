import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

/**
 * Step CRUD inside the workflow template editor.
 *
 * `tpl` provides the parent template id (for the create endpoint).
 * `steps` is the parent's reactive array — mutating it here propagates
 * to the page list rendering. Each item is expected to have shape
 * `{ id, position, title, ..., pdfTemplates: [], tasks: [] }`.
 */
export function useWeldingWorkflowSteps(tpl, steps) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const validatorRoleOptions = computed(() => [
        { value: "inspector", label: t("welding.validator_role_inspector") },
        {
            value: "quality_assurance",
            label: t("welding.validator_role_quality_assurance"),
        },
        { value: "supervisor", label: t("welding.validator_role_supervisor") },
        { value: "customer", label: t("welding.validator_role_customer") },
    ]);

    // ── Add / edit modal ──────────────────────────────────────────────────
    const modalOpen = ref(false);
    const editing = ref(null);
    const form = ref({});
    const errors = ref({});

    function openCreate() {
        editing.value = null;
        form.value = {
            position: steps.value.length,
            title: "",
            description: "",
            requiresValidation: false,
            validatorRole: "",
        };
        errors.value = {};
        modalOpen.value = true;
    }

    function openEdit(step) {
        editing.value = step;
        form.value = {
            position: step.position,
            title: step.title,
            description: step.description ?? "",
            requiresValidation: step.requiresValidation,
            validatorRole: step.validatorRole ?? "",
        };
        errors.value = {};
        modalOpen.value = true;
    }

    async function save() {
        errors.value = {};
        const payload = {
            ...form.value,
            validatorRole: form.value.validatorRole || null,
        };

        let data;
        if (editing.value) {
            data = await request(
                `/backend/welding/workflow-step-templates/${editing.value.id}/edit`,
                payload,
            );
        } else {
            payload.workflowTemplateId = tpl.value.id;
            data = await request(
                "/backend/welding/workflow-step-templates",
                payload,
            );
        }
        if (!data) return;
        if (data.success) {
            if (editing.value) {
                const idx = steps.value.findIndex(
                    (s) => s.id === editing.value.id,
                );
                steps.value[idx] = { ...steps.value[idx], ...data.step };
            } else {
                steps.value.push({ ...data.step, pdfTemplates: [], tasks: [] });
            }
            modalOpen.value = false;
            toast.success(
                t(
                    editing.value
                        ? "welding.editor.step_updated"
                        : "welding.editor.step_added",
                ),
            );
        } else if (data.errors) {
            errors.value = translateServerErrors(t, data.errors);
        }
    }

    // ── Delete confirmation ───────────────────────────────────────────────
    const pendingDelete = ref(null);

    async function doDelete() {
        if (!pendingDelete.value) return;
        const step = pendingDelete.value;
        const data = await request(
            `/backend/welding/workflow-step-templates/${step.id}/delete`,
            {},
        );
        if (data?.success) {
            steps.value = steps.value.filter((s) => s.id !== step.id);
            toast.success(t("welding.editor.step_deleted"));
            pendingDelete.value = null;
        }
    }

    // ── Reorder (optimistic with rollback) ────────────────────────────────
    async function move(step, delta) {
        const idx = steps.value.findIndex((s) => s.id === step.id);
        const target = idx + delta;
        if (target < 0 || target >= steps.value.length) return;

        const previous = steps.value.map((s) => ({ ...s }));
        const reordered = [...steps.value];
        [reordered[idx], reordered[target]] = [
            reordered[target],
            reordered[idx],
        ];
        reordered.forEach((s, i) => (s.position = i));
        steps.value = reordered;

        const data = await request(
            "/backend/welding/workflow-step-templates/reorder",
            {
                orderedStepIds: reordered.map((s) => s.id),
            },
        );
        if (!data?.success) {
            steps.value = previous;
            toast.error(t("welding.editor.reorder_failed"));
        }
    }

    return {
        loading,
        validatorRoleOptions,
        // Modal
        modalOpen,
        editing,
        form,
        errors,
        openCreate,
        openEdit,
        save,
        // Delete
        pendingDelete,
        doDelete,
        // Reorder
        move,
    };
}
