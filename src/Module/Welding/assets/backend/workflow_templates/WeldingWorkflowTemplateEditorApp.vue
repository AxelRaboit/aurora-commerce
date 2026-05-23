<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import {
    Plus, Pencil, Trash2, FileText, X, ArrowUp, ArrowDown, Send, Archive,
    CheckSquare, Save, AlertTriangle, Copy,
} from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppTextarea from "@/shared/components/form/input/AppTextarea.vue";
import AppCheckbox from "@/shared/components/form/toggle/AppCheckbox.vue";
import { useTemplateStatus } from "@welding/backend/composables/useWeldingStatus.js";
import { useWeldingTemplateEdit } from "./composables/useWeldingTemplateEdit.js";
import { useWeldingWorkflowSteps } from "./composables/useWeldingWorkflowSteps.js";
import { useWeldingStepPdfs } from "./composables/useWeldingStepPdfs.js";
import { useWeldingStepTasks } from "./composables/useWeldingStepTasks.js";

const props = defineProps({
    workflowTemplate: { type: Object, required: true },
    steps: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { TONE: STATUS_TONE } = useTemplateStatus();

const tpl = ref({ ...props.workflowTemplate });
const steps = ref(props.steps.map((s) => ({
    ...s,
    pdfTemplates: [...(s.pdfTemplates ?? [])],
    tasks: [...(s.tasks ?? [])],
})));

const editable = computed(() => tpl.value.status === "draft");

const {
    loading: tplEditLoading,
    editing: editingTpl,
    form: tplForm,
    errors: tplErrors,
    openEdit: openTplEdit,
    submitEdit: submitTplEdit,
    showPublishConfirm,
    doPublish,
    showCloneConfirm,
    doClone,
    showArchiveConfirm,
    doArchive,
} = useWeldingTemplateEdit(tpl);

const {
    loading: stepLoading,
    validatorRoleOptions,
    modalOpen: stepModalOpen,
    editing: editingStep,
    form: stepForm,
    errors: stepErrors,
    openCreate: openStepCreate,
    openEdit: openStepEdit,
    save: saveStep,
    pendingDelete: pendingStepDelete,
    doDelete: doDeleteStep,
    move: moveStep,
} = useWeldingWorkflowSteps(tpl, steps);

const {
    loading: pdfLoading,
    modalStep: pdfModalStep,
    pdfTemplateOptions,
    form: pdfForm,
    openAdd: openAddPdf,
    close: closePdfModal,
    save: savePdf,
    remove: removePdf,
} = useWeldingStepPdfs(steps);

const {
    loading: taskLoading,
    modalStep: taskModalStep,
    editing: editingTask,
    form: taskForm,
    errors: taskErrors,
    openCreate: openTaskCreate,
    openEdit: openTaskEdit,
    close: closeTaskModal,
    save: saveTask,
    pendingDelete: pendingTaskDelete,
    confirmDelete: confirmTaskDelete,
    doDelete: doDeleteTask,
} = useWeldingStepTasks(steps);
</script>

<template>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Hero card -->
        <div class="bg-surface border border-line rounded-lg p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-lg sm:text-xl font-bold text-primary break-words">{{ tpl.title }}</h2>
                    <p class="text-xs text-muted mt-0.5">v{{ tpl.version }}</p>
                    <div class="flex items-center gap-2 flex-wrap mt-2">
                        <AppBadge :color="STATUS_TONE[tpl.status]">
                            {{ t("welding.workflow_templates.status_" + tpl.status) }}
                        </AppBadge>
                        <AppBadge v-if="tpl.applicableTo" color="sky">{{ tpl.applicableTo }}</AppBadge>
                    </div>
                </div>
                <div class="flex items-center justify-between sm:justify-end gap-1 sm:shrink-0 self-end sm:self-auto">
                    <AppIconButton v-if="editable" color="accent" :title="t('welding.editor.edit_template')" v-on:click="openTplEdit">
                        <Pencil class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton v-if="tpl.status === 'draft'" color="emerald" :title="t('welding.workflow_templates.publish')" v-on:click="showPublishConfirm = true">
                        <Send class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton v-if="tpl.status === 'published'" color="sky" :title="t('welding.workflow_templates.clone')" v-on:click="showCloneConfirm = true">
                        <Copy class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton v-if="tpl.status !== 'archived'" color="amber" :title="t('welding.workflow_templates.archive')" v-on:click="showArchiveConfirm = true">
                        <Archive class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                </div>
            </div>

            <dl v-if="tpl.description" class="mt-6 pt-4 border-t border-line">
                <dt class="text-xs text-muted uppercase tracking-wide mb-1">
                    {{ t("welding.workflow_templates.field_description") }}
                </dt>
                <dd class="text-secondary text-sm whitespace-pre-wrap break-words">{{ tpl.description }}</dd>
            </dl>

            <p v-if="!editable" class="mt-6 pt-4 border-t border-line flex items-start gap-2 text-xs text-amber-600 dark:text-amber-400">
                <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5" :stroke-width="2" />
                <span>{{ t("welding.editor.locked_warning") }}</span>
            </p>
        </div>

        <!-- Steps -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-primary uppercase tracking-wide">{{ t("welding.editor.steps") }}</h3>
                <AppButton v-if="editable" variant="primary" size="md" v-on:click="openStepCreate">
                    <Plus class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("welding.editor.add_step") }}
                </AppButton>
            </div>

            <div v-if="steps.length === 0" class="bg-surface border border-dashed border-line rounded-lg p-6 text-sm text-secondary text-center">
                {{ t("welding.editor.no_steps_yet") }}
            </div>

            <ol v-else class="space-y-3">
                <li
                    v-for="(step, idx) in steps"
                    :key="step.id"
                    class="bg-surface border border-line rounded-lg p-4 sm:p-5 space-y-4"
                >
                    <!-- Step header -->
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-mono text-muted">#{{ step.position + 1 }}</span>
                                <h4 class="text-base font-bold text-primary break-words">{{ step.title }}</h4>
                            </div>
                            <div v-if="step.requiresValidation" class="flex items-center gap-1.5 flex-wrap mt-2">
                                <AppBadge color="amber">{{ t("welding.editor.requires_validation") }}</AppBadge>
                                <AppBadge v-if="step.validatorRole" color="slate">
                                    {{ t("welding.validator_role_" + step.validatorRole) }}
                                </AppBadge>
                            </div>
                            <p v-if="step.description" class="text-sm text-secondary whitespace-pre-wrap break-words mt-2">
                                {{ step.description }}
                            </p>
                        </div>
                        <div v-if="editable" class="flex items-center gap-1 sm:shrink-0 self-end sm:self-auto">
                            <AppIconButton
                                :title="t('welding.editor.move_step_up')"
                                :disabled="idx === 0 || stepLoading"
                                v-on:click="moveStep(step, -1)"
                            >
                                <ArrowUp class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton
                                :title="t('welding.editor.move_step_down')"
                                :disabled="idx === steps.length - 1 || stepLoading"
                                v-on:click="moveStep(step, 1)"
                            >
                                <ArrowDown class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton color="accent" :title="t('welding.editor.edit_step')" v-on:click="openStepEdit(step)">
                                <Pencil class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton color="rose" :title="t('welding.editor.delete_step')" :disabled="stepLoading" v-on:click="pendingStepDelete = step">
                                <Trash2 class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                        </div>
                    </div>

                    <!-- Tasks for this step -->
                    <div class="pt-4 border-t border-line space-y-2">
                        <div class="flex items-center justify-between">
                            <dt class="text-xs text-muted uppercase tracking-wide">{{ t("welding.editor.tasks") }}</dt>
                            <AppButton v-if="editable" variant="ghost" size="sm" v-on:click="openTaskCreate(step)">
                                <Plus class="w-3 h-3" :stroke-width="2" /> {{ t("welding.editor.add_task") }}
                            </AppButton>
                        </div>
                        <ul v-if="step.tasks.length > 0" class="space-y-1.5">
                            <li
                                v-for="task in step.tasks"
                                :key="task.id"
                                class="flex items-start justify-between gap-2 bg-surface-2 rounded p-2.5 text-sm"
                            >
                                <div class="flex items-start gap-2 min-w-0 flex-1">
                                    <CheckSquare class="w-4 h-4 text-secondary shrink-0 mt-0.5" :stroke-width="1.5" />
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-medium text-primary">{{ task.label }}</span>
                                            <AppBadge v-if="task.required" color="rose">{{ t("welding.editor.field_required") }}</AppBadge>
                                        </div>
                                        <p v-if="task.description" class="text-xs text-secondary whitespace-pre-wrap mt-0.5">{{ task.description }}</p>
                                    </div>
                                </div>
                                <div v-if="editable" class="flex gap-1 shrink-0">
                                    <AppIconButton color="accent" :title="t('welding.editor.edit_task')" v-on:click="openTaskEdit(step, task)">
                                        <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton color="rose" :title="t('welding.editor.delete_task')" :disabled="taskLoading" v-on:click="confirmTaskDelete(step, task)">
                                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                                    </AppIconButton>
                                </div>
                            </li>
                        </ul>
                        <p v-else class="text-xs text-muted">{{ t("welding.editor.no_tasks_for_step") }}</p>
                    </div>

                    <!-- PDFs for this step -->
                    <div class="pt-4 border-t border-line space-y-2">
                        <div class="flex items-center justify-between">
                            <dt class="text-xs text-muted uppercase tracking-wide">{{ t("welding.editor.required_pdfs") }}</dt>
                            <AppButton v-if="editable" variant="ghost" size="sm" v-on:click="openAddPdf(step)">
                                <Plus class="w-3 h-3" :stroke-width="2" /> {{ t("welding.editor.add_pdf") }}
                            </AppButton>
                        </div>
                        <ul v-if="step.pdfTemplates.length > 0" class="space-y-1.5">
                            <li
                                v-for="entry in step.pdfTemplates"
                                :key="entry.id"
                                class="flex items-center justify-between gap-2 bg-surface-2 rounded p-2.5 text-sm"
                            >
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <FileText class="w-4 h-4 text-secondary shrink-0" :stroke-width="1.5" />
                                    <span class="truncate text-primary">{{ entry.pdfTemplateName }}</span>
                                    <AppBadge v-if="entry.required" color="rose">{{ t("welding.editor.field_required") }}</AppBadge>
                                </div>
                                <AppIconButton
                                    v-if="editable"
                                    color="rose"
                                    :title="t('welding.editor.remove_pdf')"
                                    :disabled="pdfLoading"
                                    v-on:click="removePdf(step, entry)"
                                >
                                    <X class="w-3.5 h-3.5" :stroke-width="2" />
                                </AppIconButton>
                            </li>
                        </ul>
                        <p v-else class="text-xs text-muted">{{ t("welding.editor.no_pdfs_for_step") }}</p>
                    </div>
                </li>
            </ol>
        </div>

        <!-- Template edit modal -->
        <AppModal
            :show="editingTpl"
            :title="t('welding.editor.edit_template')"
            :icon="Pencil"
            :closeable="false"
            v-on:close="editingTpl = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitTplEdit">
                <AppInput
                    v-model="tplForm.title"
                    :label="t('welding.workflow_templates.field_title')"
                    :placeholder="t('welding.workflow_templates.field_title_placeholder')"
                    :error="tplErrors.title"
                    required
                />
                <AppTextarea
                    v-model="tplForm.description"
                    :label="t('welding.workflow_templates.field_description')"
                    :placeholder="t('welding.workflow_templates.field_description_placeholder')"
                    :rows="3"
                />
                <AppInput
                    v-model="tplForm.applicableTo"
                    :label="t('welding.workflow_templates.field_applicable_to')"
                    :placeholder="t('welding.workflow_templates.field_applicable_to_placeholder')"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="editingTpl = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="tplEditLoading"
                        v-on:click="submitTplEdit"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Step modal -->
        <AppModal
            :show="stepModalOpen"
            :title="t(editingStep ? 'welding.editor.edit_step' : 'welding.editor.add_step')"
            :icon="editingStep ? Pencil : Plus"
            :closeable="false"
            v-on:close="stepModalOpen = false"
        >
            <form class="space-y-4" v-on:submit.prevent="saveStep">
                <AppInput
                    v-model="stepForm.title"
                    :label="t('welding.editor.field_step_title')"
                    :placeholder="t('welding.editor.field_step_title_placeholder')"
                    :error="stepErrors.title"
                    required
                />
                <AppTextarea
                    v-model="stepForm.description"
                    :label="t('welding.editor.field_step_description')"
                    :placeholder="t('welding.editor.field_step_description_placeholder')"
                    :rows="3"
                />
                <AppCheckbox v-model="stepForm.requiresValidation" :label="t('welding.editor.requires_validation')" />
                <AppMultiselect
                    v-if="stepForm.requiresValidation"
                    v-model="stepForm.validatorRole"
                    :options="validatorRoleOptions"
                    :label="t('welding.editor.field_validator_role')"
                    :placeholder="t('welding.editor.field_validator_role_placeholder')"
                    required
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="stepModalOpen = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="stepLoading"
                        v-on:click="saveStep"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Add PDF modal -->
        <AppModal
            :show="pdfModalStep !== null"
            :title="t('welding.editor.add_pdf')"
            :icon="FileText"
            :closeable="false"
            v-on:close="closePdfModal"
        >
            <form class="space-y-4" v-on:submit.prevent="savePdf">
                <AppMultiselect
                    v-model="pdfForm.pdfTemplateId"
                    :options="pdfTemplateOptions"
                    :label="t('welding.editor.field_pdf_template')"
                    :placeholder="t('welding.editor.field_pdf_template_placeholder')"
                    required
                />
                <AppCheckbox v-model="pdfForm.required" :label="t('welding.editor.field_required')" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="closePdfModal">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="pdfLoading"
                        v-on:click="savePdf"
                    >
                        <Plus class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.add") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Task add/edit modal -->
        <AppModal
            :show="taskModalStep !== null"
            :title="t(editingTask ? 'welding.editor.edit_task' : 'welding.editor.add_task')"
            :icon="CheckSquare"
            :closeable="false"
            v-on:close="closeTaskModal"
        >
            <form class="space-y-4" v-on:submit.prevent="saveTask">
                <AppInput
                    v-model="taskForm.label"
                    :label="t('welding.editor.field_task_label')"
                    :placeholder="t('welding.editor.field_task_label_placeholder')"
                    :error="taskErrors.label"
                    required
                />
                <AppTextarea
                    v-model="taskForm.description"
                    :label="t('welding.editor.field_task_description')"
                    :placeholder="t('welding.editor.field_task_description_placeholder')"
                    :rows="3"
                />
                <AppCheckbox v-model="taskForm.required" :label="t('welding.editor.field_task_required')" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="closeTaskModal">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="taskLoading"
                        v-on:click="saveTask"
                    >
                        <Save v-if="editingTask" class="w-3.5 h-3.5" :stroke-width="2" />
                        <Plus v-else class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t(editingTask ? "shared.common.save" : "shared.common.add") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Delete step confirmation -->
        <AppModal
            :show="pendingStepDelete !== null"
            max-width="sm"
            :title="t('welding.editor.delete_step')"
            :icon="Trash2"
            :closeable="false"
            v-on:close="pendingStepDelete = null"
        >
            <p class="text-sm text-primary">{{ t("welding.editor.confirm_delete_step") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingStepDelete = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="stepLoading" v-on:click="doDeleteStep">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Delete task confirmation -->
        <AppModal
            :show="pendingTaskDelete !== null"
            max-width="sm"
            :title="t('welding.editor.delete_task')"
            :icon="Trash2"
            :closeable="false"
            v-on:close="pendingTaskDelete = null"
        >
            <p class="text-sm text-primary">{{ t("welding.editor.confirm_delete_task") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingTaskDelete = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="taskLoading" v-on:click="doDeleteTask">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Publish confirmation -->
        <AppModal
            :show="showPublishConfirm"
            max-width="sm"
            :title="t('welding.workflow_templates.confirm_publish', { title: tpl.title })"
            :icon="Send"
            :closeable="false"
            v-on:close="showPublishConfirm = false"
        >
            <p class="text-sm text-secondary">{{ t("welding.workflow_templates.confirm_publish_warning") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showPublishConfirm = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" size="md" :loading="tplEditLoading" v-on:click="doPublish">
                        <Send class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("welding.workflow_templates.publish") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Clone confirmation -->
        <AppModal
            :show="showCloneConfirm"
            max-width="sm"
            :title="t('welding.workflow_templates.confirm_clone', { title: tpl.title })"
            :icon="Copy"
            :closeable="false"
            v-on:close="showCloneConfirm = false"
        >
            <p class="text-sm text-secondary">{{ t("welding.workflow_templates.confirm_clone_warning") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showCloneConfirm = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" size="md" :loading="tplEditLoading" v-on:click="doClone">
                        <Copy class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("welding.workflow_templates.clone") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Archive confirmation -->
        <AppModal
            :show="showArchiveConfirm"
            max-width="sm"
            :title="t('welding.workflow_templates.confirm_archive', { title: tpl.title })"
            :icon="Archive"
            :closeable="false"
            v-on:close="showArchiveConfirm = false"
        >
            <p class="text-sm text-secondary">{{ t("welding.workflow_templates.confirm_archive_warning") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showArchiveConfirm = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" size="md" :loading="tplEditLoading" v-on:click="doArchive">
                        <Archive class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("welding.workflow_templates.archive") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
