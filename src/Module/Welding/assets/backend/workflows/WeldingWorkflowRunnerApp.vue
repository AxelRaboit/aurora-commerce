<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { ClipboardCheck, FileText, Check, X } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { useStepStatus, useWorkflowStatus } from "@welding/backend/composables/useWeldingStatus.js";

const props = defineProps({
    workflow: { type: Object, required: true },
    steps: { type: Array, default: () => [] },
    pdfContextType: { type: String, default: "welding_step" },
});

const { t } = useI18n();

const workflowState = ref({ ...props.workflow });
const stepsState = ref(props.steps.map((s) => ({ ...s })));

const { ICON: STATUS_ICON, COLOR: STATUS_COLOR, BG: STATUS_BG } = useStepStatus();
const { COLOR: WF_STATUS_COLOR } = useWorkflowStatus();

const isTerminal = computed(() =>
    ["completed", "rejected", "archived"].includes(workflowState.value.status),
);

const isDraft = computed(() => workflowState.value.status === "draft");

const canSubmitStep = (step) =>
    !isTerminal.value &&
    (step.status === "pending" || step.status === "in_progress") &&
    step.pdfTemplates.every((p) => !p.required || p.generatedDocuments.length > 0);

const canValidateStep = (step) =>
    !isTerminal.value && step.status === "awaiting_validation";

const { loading: actionLoading, request } = useRequest();

async function refreshState() {
    const res = await fetch(`/backend/welding/workflows/${workflowState.value.id}/state`, {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
    });
    if (!res.ok) return false;
    const data = await res.json();
    if (!data.success) return false;
    workflowState.value = data.workflow;
    stepsState.value = data.steps;
    return true;
}

async function startWorkflow() {
    const data = await request(`/backend/welding/workflows/${workflowState.value.id}/start`, {});
    if (data?.success) {
        toast.success(t("welding.runner.started"));
        await refreshState();
    }
}

async function submitStep(step) {
    if (!canSubmitStep(step)) return;
    const data = await request(`/backend/welding/workflow-steps/${step.id}/submit`, {});
    if (data?.success) {
        toast.success(t("welding.runner.step_submitted"));
        await refreshState();
    }
}

// Validation modal
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
    const data = await request(`/backend/welding/workflow-steps/${validationStep.value.id}/validate`, {
        decision: validationDecision.value,
        comment: validationComment.value || null,
    });
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
    const data = await request(`/backend/welding/workflows/${workflowState.value.id}/reject`, {
        reason: rejectionReason.value,
    });
    if (data?.success) {
        toast.success(t("welding.runner.workflow_rejected"));
        rejectingWorkflow.value = false;
        await refreshState();
    }
}

async function archiveWorkflow() {
    const data = await request(`/backend/welding/workflows/${workflowState.value.id}/archive`, {});
    if (data?.success) {
        toast.success(t("welding.runner.workflow_archived"));
        await refreshState();
    }
}

function openPdfFiller(step, pdfTemplate) {
    const url = new URL("/backend/welding/pdf-documents", window.location.origin);
    url.searchParams.set("templateId", String(pdfTemplate.pdfTemplateId));
    url.searchParams.set("contextType", props.pdfContextType);
    url.searchParams.set("contextId", String(step.id));
    url.searchParams.set("returnTo", window.location.pathname);
    window.open(url.toString(), "_blank", "noopener");
}
</script>

<template>
    <div class="p-4 sm:p-6 space-y-6 max-w-5xl mx-auto">
        <!-- WeldingWorkflow header -->
        <header class="rounded-xl border border-line bg-surface p-5 space-y-3">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-accent-100 dark:bg-accent-900/30 shrink-0">
                        <ClipboardCheck class="w-6 h-6 text-accent-500" :stroke-width="1.5" />
                    </div>
                    <div class="min-w-0">
                        <div class="font-mono text-xs text-secondary">{{ workflowState.reference }}</div>
                        <h1 class="text-lg font-semibold text-primary truncate">
                            {{ workflowState.templateTitle }}
                            <span class="text-xs text-muted font-normal">v{{ workflowState.templateVersion }}</span>
                        </h1>
                        <p class="text-sm text-secondary">
                            {{ workflowState.assigneeName || t("welding.workflows.no_assignee") }}
                        </p>
                    </div>
                </div>
                <span :class="['text-xs px-3 py-1 rounded-full border border-line', WF_STATUS_COLOR[workflowState.status]]">
                    {{ t("welding.workflows.status_" + workflowState.status) }}
                </span>
            </div>

            <div v-if="workflowState.rejectionReason" class="text-sm text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-900/20 rounded p-3">
                <strong>{{ t("welding.runner.rejection_reason") }} :</strong>
                {{ workflowState.rejectionReason }}
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <AppButton v-if="isDraft" variant="primary" :loading="actionLoading" :disabled="actionLoading" v-on:click="startWorkflow">
                    {{ t("welding.runner.start_workflow") }}
                </AppButton>
                <AppButton
                    v-if="!isTerminal && !isDraft"
                    variant="danger"
                    :disabled="actionLoading"
                    v-on:click="openWorkflowReject"
                >
                    {{ t("welding.runner.reject_workflow") }}
                </AppButton>
                <AppButton
                    v-if="workflowState.status === 'completed'"
                    variant="secondary"
                    :loading="actionLoading"
                    :disabled="actionLoading"
                    v-on:click="archiveWorkflow"
                >
                    {{ t("welding.runner.archive_workflow") }}
                </AppButton>
            </div>
        </header>

        <p v-if="isDraft" class="rounded-lg border border-dashed border-line bg-surface p-6 text-center text-sm text-secondary">
            {{ t("welding.runner.start_to_see_steps") }}
        </p>

        <ol v-else class="space-y-3">
            <li
                v-for="step in stepsState"
                :key="step.id"
                :class="['rounded-xl border p-4 sm:p-5 space-y-3', STATUS_BG[step.status]]"
            >
                <div class="flex items-start gap-3">
                    <component :is="STATUS_ICON[step.status]" :class="['w-6 h-6 shrink-0 mt-0.5', STATUS_COLOR[step.status]]" :stroke-width="2" />
                    <div class="flex-1 min-w-0 space-y-1">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <h3 class="font-medium text-primary">
                                <span class="text-xs text-muted font-mono mr-2">#{{ step.position + 1 }}</span>
                                {{ step.title }}
                            </h3>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-surface-2 border border-line text-primary">
                                {{ t("welding.workflow_steps.status_" + step.status) }}
                            </span>
                        </div>
                        <p v-if="step.description" class="text-sm text-secondary whitespace-pre-line">{{ step.description }}</p>
                        <p v-if="step.requiresValidation && step.validatorRole" class="text-xs text-muted">
                            {{ t("welding.runner.requires_validation_by") }}
                            <strong>{{ t("welding.validator_role_" + step.validatorRole) }}</strong>
                        </p>
                        <div v-if="step.rejectionComment" class="text-sm text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-900/20 rounded p-2 mt-2">
                            <strong>{{ t("welding.runner.previous_rejection") }} :</strong>
                            {{ step.rejectionComment }}
                        </div>
                        <div v-if="step.validationComment" class="text-sm text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 rounded p-2 mt-2">
                            <strong>{{ t("welding.runner.validation_comment") }} :</strong>
                            {{ step.validationComment }}
                        </div>
                    </div>
                </div>

                <div v-if="step.pdfTemplates.length > 0" class="ml-9 space-y-1">
                    <h4 class="text-xs uppercase tracking-wide font-medium text-secondary">
                        {{ t("welding.runner.required_pdfs") }}
                    </h4>
                    <ul class="space-y-1">
                        <li
                            v-for="pdf in step.pdfTemplates"
                            :key="pdf.id"
                            class="flex items-center justify-between gap-2 bg-surface border border-line rounded p-2 text-sm"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <FileText class="w-4 h-4 text-secondary shrink-0" :stroke-width="1.5" />
                                <span class="truncate">{{ pdf.pdfTemplateName }}</span>
                                <span v-if="pdf.required" class="text-xs text-rose-500" :aria-label="t('welding.editor.field_required')">*</span>
                                <Check v-if="pdf.generatedDocuments.length > 0" class="w-4 h-4 text-emerald-500" :stroke-width="2" />
                            </div>
                            <AppButton
                                v-if="step.status !== 'validated'"
                                variant="ghost"
                                size="sm"
                                v-on:click="openPdfFiller(step, pdf)"
                            >
                                {{ pdf.generatedDocuments.length > 0 ? t("welding.runner.add_pdf_document") : t("welding.runner.fill_pdf") }}
                            </AppButton>
                        </li>
                    </ul>
                </div>

                <div class="ml-9 flex flex-wrap gap-2 pt-1">
                    <AppButton
                        v-if="canSubmitStep(step)"
                        variant="primary"
                        size="sm"
                        :loading="actionLoading"
                        :disabled="actionLoading"
                        v-on:click="submitStep(step)"
                    >
                        {{ t("welding.runner.submit_step") }}
                    </AppButton>
                    <AppButton
                        v-if="canValidateStep(step)"
                        variant="primary"
                        size="sm"
                        :disabled="actionLoading"
                        v-on:click="openValidation(step)"
                    >
                        {{ t("welding.runner.validate_step") }}
                    </AppButton>
                </div>
            </li>
        </ol>

        <AppModal
            :show="validationStep !== null"
            :title="validationStep ? t('welding.runner.validate_step') + ' — ' + validationStep.title : ''"
            v-on:close="closeValidation"
        >
            <div class="space-y-4">
                <div class="flex gap-2">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" v-model="validationDecision" value="validate" class="sr-only peer" />
                        <div class="border border-line rounded p-3 text-center text-sm peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 peer-checked:border-emerald-300 dark:peer-checked:border-emerald-700 peer-checked:text-emerald-700 dark:peer-checked:text-emerald-300">
                            <Check class="w-5 h-5 mx-auto mb-1" :stroke-width="2" />
                            {{ t("welding.runner.decision_validate") }}
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" v-model="validationDecision" value="reject" class="sr-only peer" />
                        <div class="border border-line rounded p-3 text-center text-sm peer-checked:bg-rose-50 dark:peer-checked:bg-rose-900/20 peer-checked:border-rose-300 dark:peer-checked:border-rose-700 peer-checked:text-rose-700 dark:peer-checked:text-rose-300">
                            <X class="w-5 h-5 mx-auto mb-1" :stroke-width="2" />
                            {{ t("welding.runner.decision_reject") }}
                        </div>
                    </label>
                </div>
                <div>
                    <label for="valComment" class="block text-xs font-medium text-secondary mb-1">{{ t("welding.runner.comment") }}</label>
                    <textarea
                        id="valComment"
                        v-model="validationComment"
                        rows="3"
                        class="w-full rounded border border-line bg-surface p-2 text-sm"
                        :placeholder="t('welding.runner.comment_placeholder')"
                    />
                </div>
            </div>
            <template #footer>
                <AppButton variant="ghost" v-on:click="closeValidation">{{ t("welding.runner.cancel") }}</AppButton>
                <AppButton variant="primary" :loading="actionLoading" :disabled="actionLoading" v-on:click="submitValidation">
                    {{ t("welding.runner.confirm") }}
                </AppButton>
            </template>
        </AppModal>

        <AppModal
            :show="rejectingWorkflow"
            :title="t('welding.runner.reject_workflow')"
            v-on:close="rejectingWorkflow = false"
        >
            <div class="space-y-3">
                <p class="text-sm text-secondary">{{ t("welding.runner.reject_workflow_warning") }}</p>
                <textarea
                    v-model="rejectionReason"
                    rows="4"
                    class="w-full rounded border border-line bg-surface p-2 text-sm"
                    :placeholder="t('welding.runner.rejection_reason_placeholder')"
                />
            </div>
            <template #footer>
                <AppButton variant="ghost" v-on:click="rejectingWorkflow = false">{{ t("welding.runner.cancel") }}</AppButton>
                <AppButton variant="danger" :loading="actionLoading" :disabled="actionLoading" v-on:click="submitWorkflowReject">
                    {{ t("welding.runner.confirm_reject") }}
                </AppButton>
            </template>
        </AppModal>
    </div>
</template>
