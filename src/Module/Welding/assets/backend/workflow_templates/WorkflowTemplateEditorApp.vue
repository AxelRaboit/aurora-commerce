<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import {
    ScrollText, Plus, Pencil, Trash2, FileText, X, ArrowUp, ArrowDown, Send, Archive,
} from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

const props = defineProps({
    workflowTemplate: { type: Object, required: true },
    steps: { type: Array, default: () => [] },
});

const { t } = useI18n();
const tpl = ref({ ...props.workflowTemplate });
const steps = ref(props.steps.map((s) => ({ ...s, pdfTemplates: [...s.pdfTemplates] })));

const editable = computed(() => tpl.value.status === "draft");

const { request } = useRequest();

// ── Template header edit ──────────────────────────────────────────────────
const editingTpl = ref(false);
const tplForm = ref({});
const tplErrors = ref({});

function openTplEdit() {
    tplForm.value = {
        title: tpl.value.title,
        description: tpl.value.description ?? "",
        applicableTo: tpl.value.applicableTo ?? "",
    };
    tplErrors.value = {};
    editingTpl.value = true;
}

async function saveTpl() {
    const data = await request(`/backend/welding/workflow-templates/${tpl.value.id}/edit`, tplForm.value);
    if (!data) return;
    if (data.success) {
        Object.assign(tpl.value, data.workflowTemplate);
        editingTpl.value = false;
        toast.success(t("welding.editor.template_updated"));
    } else if (data.errors) {
        tplErrors.value = data.errors;
    }
}

async function publishTpl() {
    const data = await request(`/backend/welding/workflow-templates/${tpl.value.id}/publish`, {});
    if (data?.success) {
        tpl.value.status = "published";
        toast.success(t("welding.workflow_templates.published"));
    }
}

async function archiveTpl() {
    const data = await request(`/backend/welding/workflow-templates/${tpl.value.id}/archive`, {});
    if (data?.success) {
        tpl.value.status = "archived";
        toast.success(t("welding.workflow_templates.archived"));
    }
}

// ── Step add/edit/delete ──────────────────────────────────────────────────
const stepModalOpen = ref(false);
const editingStep = ref(null);
const stepForm = ref({});
const stepErrors = ref({});

function openStepCreate() {
    editingStep.value = null;
    stepForm.value = {
        position: steps.value.length,
        title: "",
        description: "",
        requiresValidation: false,
        validatorRole: "",
    };
    stepErrors.value = {};
    stepModalOpen.value = true;
}

function openStepEdit(step) {
    editingStep.value = step;
    stepForm.value = {
        position: step.position,
        title: step.title,
        description: step.description ?? "",
        requiresValidation: step.requiresValidation,
        validatorRole: step.validatorRole ?? "",
    };
    stepErrors.value = {};
    stepModalOpen.value = true;
}

async function saveStep() {
    stepErrors.value = {};
    const payload = {
        ...stepForm.value,
        validatorRole: stepForm.value.validatorRole || null,
    };

    let data;
    if (editingStep.value) {
        data = await request(`/backend/welding/workflow-step-templates/${editingStep.value.id}/edit`, payload);
    } else {
        payload.workflowTemplateId = tpl.value.id;
        data = await request("/backend/welding/workflow-step-templates", payload);
    }
    if (!data) return;
    if (data.success) {
        if (editingStep.value) {
            const idx = steps.value.findIndex((s) => s.id === editingStep.value.id);
            steps.value[idx] = { ...steps.value[idx], ...data.step };
        } else {
            steps.value.push({ ...data.step, pdfTemplates: [] });
        }
        stepModalOpen.value = false;
        toast.success(t(editingStep.value ? "welding.editor.step_updated" : "welding.editor.step_added"));
    } else if (data.errors) {
        stepErrors.value = data.errors;
    }
}

async function deleteStep(step) {
    if (!confirm(t("welding.editor.confirm_delete_step"))) return;
    const data = await request(`/backend/welding/workflow-step-templates/${step.id}/delete`, {});
    if (data?.success) {
        steps.value = steps.value.filter((s) => s.id !== step.id);
        toast.success(t("welding.editor.step_deleted"));
    }
}

async function moveStep(step, delta) {
    const idx = steps.value.findIndex((s) => s.id === step.id);
    const target = idx + delta;
    if (target < 0 || target >= steps.value.length) return;

    // Optimistic local swap; revert on failure
    const previous = steps.value.map((s) => ({ ...s }));
    const reordered = [...steps.value];
    [reordered[idx], reordered[target]] = [reordered[target], reordered[idx]];
    reordered.forEach((s, i) => (s.position = i));
    steps.value = reordered;

    const data = await request("/backend/welding/workflow-step-templates/reorder", {
        orderedStepIds: reordered.map((s) => s.id),
    });
    if (!data?.success) {
        steps.value = previous;
        toast.error(t("welding.editor.reorder_failed"));
    }
}

// ── Add PDF to step ───────────────────────────────────────────────────────
const pdfModalStep = ref(null);
const pdfTemplateOptions = ref([]);
const pdfForm = ref({});

async function openAddPdf(step) {
    pdfModalStep.value = step;
    pdfForm.value = { pdfTemplateId: "", position: step.pdfTemplates.length, required: true };
    if (pdfTemplateOptions.value.length === 0) {
        const res = await fetch("/backend/welding/options/pdf-templates", { headers: { Accept: "application/json" } });
        const data = await res.json();
        if (data.success) pdfTemplateOptions.value = data.items;
    }
}

async function savePdf() {
    if (!pdfForm.value.pdfTemplateId) {
        toast.error(t("welding.editor.pdf_template_required"));
        return;
    }
    const data = await request("/backend/welding/workflow-step-pdf-templates", {
        workflowStepTemplateId: pdfModalStep.value.id,
        pdfTemplateId: Number(pdfForm.value.pdfTemplateId),
        position: pdfForm.value.position,
        required: pdfForm.value.required,
    });
    if (data?.success) {
        const stepIdx = steps.value.findIndex((s) => s.id === pdfModalStep.value.id);
        steps.value[stepIdx].pdfTemplates.push(data.entry);
        steps.value[stepIdx].pdfTemplatesCount = steps.value[stepIdx].pdfTemplates.length;
        pdfModalStep.value = null;
        toast.success(t("welding.editor.pdf_added"));
    }
}

async function removePdf(step, entry) {
    const data = await request(`/backend/welding/workflow-step-pdf-templates/${entry.id}/delete`, {});
    if (data?.success) {
        const stepIdx = steps.value.findIndex((s) => s.id === step.id);
        steps.value[stepIdx].pdfTemplates = steps.value[stepIdx].pdfTemplates.filter((p) => p.id !== entry.id);
    }
}

const STATUS_BADGE = {
    draft: "bg-gray-100 text-gray-700",
    published: "bg-emerald-100 text-emerald-700",
    archived: "bg-zinc-100 text-zinc-600",
};
</script>

<template>
    <div class="p-6 space-y-6 max-w-4xl mx-auto">
        <!-- Header -->
        <div class="rounded-xl border border-line bg-surface p-5 space-y-2">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-accent-100 dark:bg-accent-900/30">
                        <ScrollText class="w-6 h-6 text-accent-500" :stroke-width="1.5" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-lg font-semibold text-primary truncate">{{ tpl.title }}</h1>
                            <span class="text-xs text-muted">v{{ tpl.version }}</span>
                            <span :class="['text-xs px-2 py-0.5 rounded-full', STATUS_BADGE[tpl.status]]">
                                {{ t("welding.workflow_templates.status_" + tpl.status) }}
                            </span>
                        </div>
                        <p v-if="tpl.applicableTo" class="text-sm text-secondary">{{ tpl.applicableTo }}</p>
                        <p v-if="tpl.description" class="text-sm text-secondary whitespace-pre-line mt-1">{{ tpl.description }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <AppButton v-if="editable" variant="ghost" size="sm" v-on:click="openTplEdit">
                        <Pencil class="w-3.5 h-3.5" /> {{ t("welding.editor.edit_template") }}
                    </AppButton>
                    <AppButton v-if="tpl.status === 'draft'" variant="primary" size="sm" v-on:click="publishTpl">
                        <Send class="w-3.5 h-3.5" /> {{ t("welding.workflow_templates.publish") }}
                    </AppButton>
                    <AppButton v-if="tpl.status !== 'archived'" variant="ghost" size="sm" v-on:click="archiveTpl">
                        <Archive class="w-3.5 h-3.5" /> {{ t("welding.workflow_templates.archive") }}
                    </AppButton>
                </div>
            </div>
            <p v-if="!editable" class="text-xs text-amber-600 dark:text-amber-400">
                {{ t("welding.editor.locked_warning") }}
            </p>
        </div>

        <!-- Steps -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-medium text-primary">{{ t("welding.editor.steps") }}</h2>
                <AppButton v-if="editable" variant="primary" size="sm" v-on:click="openStepCreate">
                    <Plus class="w-3.5 h-3.5" /> {{ t("welding.editor.add_step") }}
                </AppButton>
            </div>

            <div v-if="steps.length === 0" class="rounded border border-dashed border-line p-6 text-sm text-secondary text-center">
                {{ t("welding.editor.no_steps_yet") }}
            </div>

            <ol v-else class="space-y-2">
                <li
                    v-for="(step, idx) in steps"
                    :key="step.id"
                    class="rounded-lg border border-line bg-surface p-4 space-y-2"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-mono text-muted">#{{ step.position + 1 }}</span>
                                <span class="font-medium text-primary truncate">{{ step.title }}</span>
                                <span v-if="step.requiresValidation" class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                    {{ t("welding.editor.requires_validation") }}
                                    <span v-if="step.validatorRole">— {{ t("welding.validator_role_" + step.validatorRole) }}</span>
                                </span>
                            </div>
                            <p v-if="step.description" class="text-sm text-secondary whitespace-pre-line mt-1">{{ step.description }}</p>
                        </div>
                        <div v-if="editable" class="flex gap-1">
                            <AppButton variant="ghost" size="sm" :disabled="idx === 0" v-on:click="moveStep(step, -1)">
                                <ArrowUp class="w-3.5 h-3.5" />
                            </AppButton>
                            <AppButton variant="ghost" size="sm" :disabled="idx === steps.length - 1" v-on:click="moveStep(step, 1)">
                                <ArrowDown class="w-3.5 h-3.5" />
                            </AppButton>
                            <AppButton variant="ghost" size="sm" v-on:click="openStepEdit(step)">
                                <Pencil class="w-3.5 h-3.5" />
                            </AppButton>
                            <AppButton variant="ghost" size="sm" v-on:click="deleteStep(step)">
                                <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                            </AppButton>
                        </div>
                    </div>

                    <!-- PDFs for this step -->
                    <div class="ml-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs uppercase tracking-wide font-medium text-secondary">{{ t("welding.editor.required_pdfs") }}</h3>
                            <AppButton v-if="editable" variant="ghost" size="sm" v-on:click="openAddPdf(step)">
                                <Plus class="w-3 h-3" /> {{ t("welding.editor.add_pdf") }}
                            </AppButton>
                        </div>
                        <ul v-if="step.pdfTemplates.length > 0" class="space-y-1">
                            <li
                                v-for="entry in step.pdfTemplates"
                                :key="entry.id"
                                class="flex items-center justify-between gap-2 bg-surface-2 rounded p-2 text-sm"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <FileText class="w-4 h-4 text-secondary flex-shrink-0" :stroke-width="1.5" />
                                    <span class="truncate">{{ entry.pdfTemplateName }}</span>
                                    <span v-if="entry.required" class="text-xs text-rose-500">*</span>
                                </div>
                                <AppButton v-if="editable" variant="ghost" size="sm" v-on:click="removePdf(step, entry)">
                                    <X class="w-3.5 h-3.5" />
                                </AppButton>
                            </li>
                        </ul>
                        <p v-else class="text-xs text-muted ml-1">{{ t("welding.editor.no_pdfs_for_step") }}</p>
                    </div>
                </li>
            </ol>
        </div>

        <!-- Template edit modal -->
        <AppModal :show="editingTpl" :title="t('welding.editor.edit_template')" v-on:close="editingTpl = false">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-secondary mb-1">{{ t("welding.workflow_templates.field_title") }} *</label>
                    <input v-model="tplForm.title" type="text" class="w-full rounded border border-line bg-surface p-2 text-sm" />
                    <p v-if="tplErrors.title" class="text-xs text-rose-500 mt-1">{{ tplErrors.title }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-secondary mb-1">{{ t("welding.workflow_templates.field_description") }}</label>
                    <textarea v-model="tplForm.description" rows="3" class="w-full rounded border border-line bg-surface p-2 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-secondary mb-1">{{ t("welding.workflow_templates.field_applicable_to") }}</label>
                    <input v-model="tplForm.applicableTo" type="text" class="w-full rounded border border-line bg-surface p-2 text-sm" />
                </div>
            </div>
            <template #footer>
                <AppButton variant="ghost" v-on:click="editingTpl = false">{{ t("welding.runner.cancel") }}</AppButton>
                <AppButton variant="primary" v-on:click="saveTpl">{{ t("welding.runner.confirm") }}</AppButton>
            </template>
        </AppModal>

        <!-- Step modal -->
        <AppModal :show="stepModalOpen" :title="t(editingStep ? 'welding.editor.edit_step' : 'welding.editor.add_step')" v-on:close="stepModalOpen = false">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-secondary mb-1">{{ t("welding.editor.field_step_title") }} *</label>
                    <input v-model="stepForm.title" type="text" class="w-full rounded border border-line bg-surface p-2 text-sm" />
                    <p v-if="stepErrors.title" class="text-xs text-rose-500 mt-1">{{ stepErrors.title }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-secondary mb-1">{{ t("welding.workflow_templates.field_description") }}</label>
                    <textarea v-model="stepForm.description" rows="2" class="w-full rounded border border-line bg-surface p-2 text-sm"></textarea>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" v-model="stepForm.requiresValidation" />
                    {{ t("welding.editor.requires_validation") }}
                </label>
                <div v-if="stepForm.requiresValidation">
                    <label class="block text-xs font-medium text-secondary mb-1">{{ t("welding.editor.field_validator_role") }} *</label>
                    <select v-model="stepForm.validatorRole" class="w-full rounded border border-line bg-surface p-2 text-sm">
                        <option value="">—</option>
                        <option value="inspector">{{ t("welding.validator_role_inspector") }}</option>
                        <option value="quality_assurance">{{ t("welding.validator_role_quality_assurance") }}</option>
                        <option value="supervisor">{{ t("welding.validator_role_supervisor") }}</option>
                        <option value="customer">{{ t("welding.validator_role_customer") }}</option>
                    </select>
                </div>
            </div>
            <template #footer>
                <AppButton variant="ghost" v-on:click="stepModalOpen = false">{{ t("welding.runner.cancel") }}</AppButton>
                <AppButton variant="primary" v-on:click="saveStep">{{ t("welding.runner.confirm") }}</AppButton>
            </template>
        </AppModal>

        <!-- Add PDF modal -->
        <AppModal :show="pdfModalStep !== null" :title="t('welding.editor.add_pdf')" v-on:close="pdfModalStep = null">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-secondary mb-1">{{ t("welding.editor.field_pdf_template") }} *</label>
                    <select v-model="pdfForm.pdfTemplateId" class="w-full rounded border border-line bg-surface p-2 text-sm">
                        <option value="">—</option>
                        <option v-for="opt in pdfTemplateOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" v-model="pdfForm.required" />
                    {{ t("welding.editor.field_required") }}
                </label>
            </div>
            <template #footer>
                <AppButton variant="ghost" v-on:click="pdfModalStep = null">{{ t("welding.runner.cancel") }}</AppButton>
                <AppButton variant="primary" v-on:click="savePdf">{{ t("welding.runner.confirm") }}</AppButton>
            </template>
        </AppModal>
    </div>
</template>
