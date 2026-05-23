import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Runner page logic — welder + validator interactions:
 *   - Start / archive a workflow
 *   - Submit a step (gated by required PDFs done + required tasks done)
 *   - Toggle a checklist task done/undone
 *   - Open a validation modal (validate or reject a submitted step)
 *   - Reject the whole workflow (terminal)
 *   - Open the PDF filler in a new tab (cross-context redirect)
 *
 * State is mirrored locally from the initial SSR payload, then refreshed
 * from the `/state` endpoint after every mutation.
 */
export function useWorkflowRunner(
    initialWorkflow,
    initialSteps,
    pdfContextType,
) {
    const { t } = useI18n();
    const { loading: actionLoading, request } = useRequest();

    const workflowState = ref({ ...initialWorkflow });
    const stepsState = ref(initialSteps.map((s) => ({ ...s })));

    const isTerminal = computed(() =>
        ["completed", "rejected", "archived"].includes(
            workflowState.value.status,
        ),
    );
    const isDraft = computed(() => workflowState.value.status === "draft");

    function canSubmitStep(step) {
        return (
            !isTerminal.value &&
            (step.status === "pending" || step.status === "in_progress") &&
            step.pdfTemplates.every(
                (p) => !p.required || p.generatedDocuments.length > 0,
            ) &&
            (step.tasks ?? []).every((task) => !task.required || task.done)
        );
    }

    function stepIsActionable(step) {
        return (
            !isTerminal.value &&
            (step.status === "pending" || step.status === "in_progress")
        );
    }

    function canValidateStep(step) {
        return !isTerminal.value && step.status === "awaiting_validation";
    }

    async function refreshState() {
        const res = await fetch(
            `/backend/welding/workflows/${workflowState.value.id}/state`,
            {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            },
        );
        if (!res.ok) return false;
        const data = await res.json();
        if (!data.success) return false;
        workflowState.value = data.workflow;
        stepsState.value = data.steps;
        return true;
    }

    // ── Workflow-level actions ────────────────────────────────────────────
    async function startWorkflow() {
        const data = await request(
            `/backend/welding/workflows/${workflowState.value.id}/start`,
            {},
        );
        if (data?.success) {
            toast.success(t("welding.runner.started"));
            await refreshState();
        }
    }

    async function archiveWorkflow() {
        const data = await request(
            `/backend/welding/workflows/${workflowState.value.id}/archive`,
            {},
        );
        if (data?.success) {
            toast.success(t("welding.runner.workflow_archived"));
            await refreshState();
        }
    }

    // Reject-workflow modal
    const rejectingWorkflow = ref(false);
    const rejectionReason = ref("");

    function openWorkflowReject() {
        rejectingWorkflow.value = true;
        rejectionReason.value = "";
    }

    async function submitWorkflowReject() {
        if (!rejectionReason.value.trim()) {
            toast.error(t("welding.workflows.errors.reason_required"));
            return;
        }
        const data = await request(
            `/backend/welding/workflows/${workflowState.value.id}/reject`,
            {
                reason: rejectionReason.value,
            },
        );
        if (data?.success) {
            toast.success(t("welding.runner.workflow_rejected"));
            rejectingWorkflow.value = false;
            await refreshState();
        }
    }

    // ── Step-level actions ────────────────────────────────────────────────
    async function submitStep(step) {
        if (!canSubmitStep(step)) return;
        const data = await request(
            `/backend/welding/workflow-steps/${step.id}/submit`,
            {},
        );
        if (!data) return;
        if (data.success) {
            toast.success(t("welding.runner.step_submitted"));
            await refreshState();
        } else if (data.error) {
            toast.error(t(data.error));
        }
    }

    async function toggleTask(step, task) {
        if (!stepIsActionable(step)) return;
        const data = await request(
            `/backend/welding/workflow-step-tasks/${task.id}/toggle`,
            {
                done: !task.done,
            },
        );
        if (data?.success && data.task) {
            const stepIdx = stepsState.value.findIndex((s) => s.id === step.id);
            if (stepIdx >= 0) {
                const taskIdx = stepsState.value[stepIdx].tasks.findIndex(
                    (t) => t.id === task.id,
                );
                if (taskIdx >= 0)
                    stepsState.value[stepIdx].tasks[taskIdx] = data.task;
            }
        }
    }

    // Validation modal (per-step validate/reject decision)
    const validationStep = ref(null);
    const validationComment = ref("");
    const validationDecision = ref("validate");

    function openValidation(step) {
        validationStep.value = step;
        validationComment.value = "";
        validationDecision.value = "validate";
    }

    function closeValidation() {
        validationStep.value = null;
    }

    async function submitValidation() {
        if (!validationStep.value) return;
        const data = await request(
            `/backend/welding/workflow-steps/${validationStep.value.id}/validate`,
            {
                decision: validationDecision.value,
                comment: validationComment.value || null,
            },
        );
        if (data?.success) {
            toast.success(
                t(
                    validationDecision.value === "validate"
                        ? "welding.runner.step_validated"
                        : "welding.runner.step_rejected_by_validator",
                ),
            );
            closeValidation();
            await refreshState();
        }
    }

    // ── PDF filler (cross-page redirect) ──────────────────────────────────
    function openPdfFiller(step, pdfTemplate) {
        const url = new URL(
            "/backend/welding/pdf-documents",
            window.location.origin,
        );
        url.searchParams.set("templateId", String(pdfTemplate.pdfTemplateId));
        url.searchParams.set("contextType", pdfContextType);
        url.searchParams.set("contextId", String(step.id));
        url.searchParams.set("returnTo", window.location.pathname);
        window.open(url.toString(), "_blank", "noopener");
    }

    return {
        // State
        workflowState,
        stepsState,
        isTerminal,
        isDraft,
        actionLoading,
        // Guards
        canSubmitStep,
        stepIsActionable,
        canValidateStep,
        // Workflow actions
        startWorkflow,
        archiveWorkflow,
        rejectingWorkflow,
        rejectionReason,
        openWorkflowReject,
        submitWorkflowReject,
        // Step actions
        submitStep,
        toggleTask,
        // Validation modal
        validationStep,
        validationComment,
        validationDecision,
        openValidation,
        closeValidation,
        submitValidation,
        // PDF filler
        openPdfFiller,
    };
}
