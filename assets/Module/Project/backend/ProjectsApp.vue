<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { VueDraggable } from "vue-draggable-plus";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useProjectsListPage } from "./composables/useProjectsListPage.js";
import { useProjectsCreate } from "./composables/useProjectsCreate.js";
import { useProjectsEdit } from "./composables/useProjectsEdit.js";
import { useProjectsDelete } from "./composables/useProjectsDelete.js";
import { useProjectDetail } from "./composables/useProjectDetail.js";
import { useAutoSelectFirst } from "./composables/useAutoSelectFirst.js";
import { useTasksCreate } from "./composables/useTasksCreate.js";
import { useTasksEdit } from "./composables/useTasksEdit.js";
import { useTasksKanban } from "./composables/useTasksKanban.js";
import { useColumnsManage } from "./composables/useColumnsManage.js";
import { useProjectActivity } from "./composables/useProjectActivity.js";
import { useProjectFormOptions } from "./composables/useProjectFormOptions.js";
import { useLabelsManage, LABEL_COLORS } from "./composables/useLabelsManage.js";
import { useTaskExtras } from "./composables/useTaskExtras.js";
import { useSprintsManage } from "./composables/useSprintsManage.js";
import { useSavedViews } from "./composables/useSavedViews.js";
import { PROJECT_STATUS_TONE } from "@/Module/Project/utils/enums/projectStatus.js";
import { TASK_PRIORITY_TONE } from "@/Module/Project/utils/enums/projectTaskPriority.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppDatePicker from "@/shared/components/form/AppDatePicker.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import { Plus, Pencil, Trash2, Activity, X, MessageSquare, CheckSquare, Square, Clock, Paperclip, Tag, Calendar, Bookmark, FileText } from "lucide-vue-next";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    showPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    taskCreatePath: { type: String, required: true },
    taskUpdatePath: { type: String, required: true },
    taskDeletePath: { type: String, required: true },
    taskReorderPath: { type: String, required: true },
    columnCreatePath: { type: String, required: true },
    columnUpdatePath: { type: String, required: true },
    columnDeletePath: { type: String, required: true },
    columnReorderPath: { type: String, required: true },
    activityPath: { type: String, required: true },
    labelCreatePath: { type: String, default: "" },
    labelUpdatePath: { type: String, default: "" },
    labelDeletePath: { type: String, default: "" },
    taskItemsReplacePath: { type: String, default: "" },
    taskTimeEntryCreatePath: { type: String, default: "" },
    taskTimeEntryDeletePath: { type: String, default: "" },
    taskCommentCreatePath: { type: String, default: "" },
    taskCommentDeletePath: { type: String, default: "" },
    taskAttachmentsAttachPath: { type: String, default: "" },
    taskAttachmentDetachPath: { type: String, default: "" },
    sprintCreatePath: { type: String, default: "" },
    sprintUpdatePath: { type: String, default: "" },
    sprintDeletePath: { type: String, default: "" },
    savedViewListPath: { type: String, default: "" },
    savedViewCreatePath: { type: String, default: "" },
    savedViewDeletePath: { type: String, default: "" },
    generateInvoicePath: { type: String, default: "" },
    statusOptions: { type: Array, default: () => [] },
    priorityOptions: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    crmContacts: { type: Array, default: () => [] },
    crmCompanies: { type: Array, default: () => [] },
    crmDeals: { type: Array, default: () => [] },
});

const {
    items: projects,
    page,
    totalPages,
    search: searchInput,
    onSearch,
    goToPage,
    reload: reloadProjects,
    statusFilter,
    setStatusFilter,
    listLoading,
} = useProjectsListPage(props);

const {
    activeProject,
    activeTasks,
    localColumns,
    tasksByColumn,
    openProject,
    closeDetail,
    reloadProject,
} = useProjectDetail(props.showPath);

useAutoSelectFirst(projects, activeProject, openProject);

const {
    showCreate: showProjectModal,
    newProject,
    createErrors: projectCreateErrors,
    createLoading: projectCreateLoading,
    openCreate: openCreateProject,
    submitCreate: submitCreateProject,
} = useProjectsCreate(props.createPath, reloadProjects);

const {
    showEdit: showEditProjectModal,
    editingProject,
    editForm: editProjectForm,
    editErrors: projectEditErrors,
    editLoading: projectEditLoading,
    openEdit: openEditProject,
    submitEdit: submitEditProject,
} = useProjectsEdit(props.updatePath, reloadProjects, activeProject);

const {
    showCreateTask,
    newTask,
    createTaskErrors,
    createTaskLoading,
    openCreateTask,
    submitCreateTask,
} = useTasksCreate(props.taskCreatePath, activeProject, reloadProject);

const {
    showEditTask,
    editingTask,
    editTaskForm,
    editTaskErrors,
    editTaskLoading,
    openEditTask,
    submitEditTask,
} = useTasksEdit(props.taskUpdatePath, reloadProject);

const { onColumnEnd, onColumnAdd } = useTasksKanban(
    props.taskReorderPath,
    activeProject,
    localColumns,
);

const {
    showCreateColumn,
    newColumn,
    createColumnErrors,
    createColumnLoading,
    openCreateColumn,
    submitCreateColumn,
    showRenameColumn,
    editingColumn,
    renameForm,
    renameErrors,
    renameLoading,
    openRenameColumn,
    submitRenameColumn,
    pendingDeleteColumn,
    deleteColumnLoading,
    confirmDeleteColumn,
    doDeleteColumn,
    orderedColumns,
    persistColumnsOrder,
} = useColumnsManage(
    {
        create: props.columnCreatePath,
        update: props.columnUpdatePath,
        delete: props.columnDeletePath,
        reorder: props.columnReorderPath,
    },
    activeProject,
    reloadProject,
);

const {
    pendingDeleteProject,
    projectDeleting,
    confirmDeleteProject,
    doDeleteProject,
    pendingDeleteTask,
    taskDeleting,
    confirmDeleteTask,
    doDeleteTask,
} = useProjectsDelete({
    projectDeletePath: props.deletePath,
    taskDeletePath: props.taskDeletePath,
    activeProject,
    activeTasks,
    closeDetail,
    reloadProjects,
});

const isEditModal = computed(() => editingProject.value !== null);
const isEditTaskModal = computed(() => editingTask.value !== null);

const { userOptions, contactOptions, companyOptions, dealOptions } = useProjectFormOptions(props);

const labelsManage = useLabelsManage(
    { create: props.labelCreatePath, update: props.labelUpdatePath, delete: props.labelDeletePath },
    activeProject,
    reloadProject,
);

const taskExtras = useTaskExtras(
    {
        commentCreate: props.taskCommentCreatePath,
        commentDelete: props.taskCommentDeletePath,
        itemsReplace: props.taskItemsReplacePath,
        timeEntryCreate: props.taskTimeEntryCreatePath,
        timeEntryDelete: props.taskTimeEntryDeletePath,
        attachmentsAttach: props.taskAttachmentsAttachPath,
        attachmentDetach: props.taskAttachmentDetachPath,
    },
    editingTask,
    reloadProject,
);

const sprintsManage = useSprintsManage(
    { create: props.sprintCreatePath, update: props.sprintUpdatePath, delete: props.sprintDeletePath },
    activeProject,
    reloadProject,
);

const savedViews = useSavedViews(
    { list: props.savedViewListPath, create: props.savedViewCreatePath, delete: props.savedViewDeletePath },
    activeProject,
);

const sprintOptions = computed(() =>
    (activeProject.value?.sprints ?? []).map((sprint) => ({ value: sprint.id, label: sprint.name })),
);

async function generateInvoice() {
    if (!activeProject.value || !props.generateInvoicePath) return;
    if (!confirm(t("backend.projects.confirmGenerateInvoice"))) return;
    const url = props.generateInvoicePath.replace("__id__", activeProject.value.id);
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
        });
        const data = await response.json();
        if (data.success && data.invoiceId) {
            window.location.href = `/backend/billing/invoices/${data.invoiceId}`;
        }
    } catch {
        // toast handled elsewhere
    }
}

// Labels available for tasks of the active project (read from project payload).
const labelOptions = computed(() =>
    (activeProject.value?.labels ?? []).map((label) => ({
        value: label.id,
        label: label.name,
    })),
);
const labelsById = computed(() => {
    const map = {};
    for (const label of activeProject.value?.labels ?? []) {
        map[label.id] = label;
    }
    return map;
});

const {
    entries: activityEntries,
    loading: activityLoading,
    reload: reloadActivity,
} = useProjectActivity(props.activityPath, activeProject);

const showActivity = ref(false);
function toggleActivity() {
    showActivity.value = !showActivity.value;
    if (showActivity.value) reloadActivity();
}

function formatRelativeDate(iso) {
    const date = new Date(iso);
    const diffSeconds = Math.round((Date.now() - date.getTime()) / 1000);
    if (diffSeconds < 60) return t("backend.projects.activity.justNow");
    if (diffSeconds < 3600) return t("backend.projects.activity.minutesAgo", { n: Math.floor(diffSeconds / 60) });
    if (diffSeconds < 86400) return t("backend.projects.activity.hoursAgo", { n: Math.floor(diffSeconds / 3600) });
    return date.toLocaleDateString();
}
</script>

<template>
    <div class="space-y-4">
        <!-- Top: full-width search + create button -->
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('backend.projects.searchPlaceholder')"
                v-on:search="onSearch"
            />
            <AppButton
                v-if="can('project.projects.create')"
                variant="primary"
                size="md"
                class="w-full sm:w-auto"
                v-on:click="openCreateProject"
            >
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('backend.projects.add') }}
            </AppButton>
        </div>

        <!-- Status filter tabs -->
        <div class="flex gap-1 flex-wrap">
            <AppTab
                size="sm"
                :active="statusFilter === ''"
                v-on:click="setStatusFilter('')"
            >
                {{ t('backend.projects.statusFilter.all') }}
            </AppTab>
            <AppTab
                v-for="opt in statusOptions"
                :key="opt.value"
                size="sm"
                :active="statusFilter === opt.value"
                v-on:click="setStatusFilter(opt.value)"
            >
                {{ opt.label }}
            </AppTab>
        </div>

        <!-- Empty state — full width when no projects -->
        <AppNoData v-if="!listLoading && !projects.length" :message="t('backend.projects.empty')" />

        <!-- 2-column: list + detail -->
        <div v-else class="flex flex-col lg:flex-row gap-4 min-h-0">
            <!-- Left: project list -->
            <div class="w-full lg:w-80 shrink-0 space-y-3">
                <div class="space-y-1">
                    <button
                        v-for="project in projects"
                        :key="project.id"
                        type="button"
                        class="w-full text-left p-3 rounded-xl border transition-colors"
                        :class="activeProject?.id === project.id ? 'bg-accent-600/10 border-accent-600/30' : 'bg-surface border-line/60 hover:bg-surface-2'"
                        v-on:click="openProject(project)"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm text-primary truncate">{{ project.title }}</p>
                                <p v-if="project.reference" class="text-xs text-muted mt-0.5">{{ project.reference }}</p>
                            </div>
                            <AppBadge :color="PROJECT_STATUS_TONE[project.status] ?? 'slate'" class="shrink-0">
                                {{ project.statusLabel }}
                            </AppBadge>
                        </div>
                        <div class="flex items-center gap-2 mt-2 text-xs text-secondary">
                            <span>{{ project.taskCount }} {{ t('backend.projects.tasks').toLowerCase() }}</span>
                            <span v-if="project.responsibleUser">· {{ project.responsibleUser.name }}</span>
                        </div>
                    </button>
                </div>

                <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:change="goToPage" />
            </div>

            <!-- Right panel: project detail -->
            <div v-if="activeProject" class="flex-1 min-w-0 space-y-4">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg font-semibold text-primary truncate">{{ activeProject.title }}</h2>
                            <AppBadge :color="PROJECT_STATUS_TONE[activeProject.status] ?? 'slate'">
                                {{ activeProject.statusLabel }}
                            </AppBadge>
                        </div>
                        <p v-if="activeProject.description" class="text-sm text-secondary mt-1">{{ activeProject.description }}</p>
                        <div class="flex flex-wrap gap-3 mt-2 text-xs text-muted">
                            <span v-if="activeProject.startDate">{{ t('backend.projects.fields.start') }} {{ activeProject.startDate }}</span>
                            <span v-if="activeProject.endDate">{{ t('backend.projects.fields.end') }} {{ activeProject.endDate }}</span>
                            <span v-if="activeProject.responsibleUser">{{ t('backend.projects.fields.responsibleLabel') }} {{ activeProject.responsibleUser.name }}</span>
                            <span v-if="activeProject.crmContacts && activeProject.crmContacts.length">{{ t('backend.projects.fields.contactsLabel') }} {{ activeProject.crmContacts.map((contact) => contact.name).join(', ') }}</span>
                            <span v-if="activeProject.crmCompany">{{ t('backend.projects.fields.companyLabel') }} {{ activeProject.crmCompany.name }}</span>
                            <span v-if="activeProject.crmDeal">{{ t('backend.projects.fields.dealLabel') }} {{ activeProject.crmDeal.name }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-0.5 shrink-0">
                        <AppIconButton color="accent" :title="t('backend.projects.labels.manage')" v-on:click="labelsManage.openLabelsModal">
                            <Tag class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton color="accent" :title="t('backend.projects.sprints.manage')" v-on:click="sprintsManage.openSprintsModal">
                            <Calendar class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton color="accent" :title="t('backend.projects.activity.title')" v-on:click="toggleActivity">
                            <Activity class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton
                            v-if="activeProject.status === 'completed' && can('project.projects.edit') && generateInvoicePath"
                            color="emerald"
                            :title="t('backend.projects.generateInvoice')"
                            v-on:click="generateInvoice"
                        >
                            <FileText class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton v-if="can('project.projects.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEditProject(activeProject)">
                            <Pencil class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton v-if="can('project.projects.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDeleteProject(activeProject)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </div>

                <!-- Activity timeline (collapsible) -->
                <div v-if="showActivity" class="bg-surface-2 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-primary">{{ t('backend.projects.activity.title') }}</h3>
                        <AppIconButton :title="t('shared.common.close')" v-on:click="showActivity = false">
                            <X class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                    <p v-if="activityLoading" class="text-xs text-muted">{{ t('shared.common.loading') }}</p>
                    <p v-else-if="!activityEntries.length" class="text-xs text-muted">{{ t('backend.projects.activity.empty') }}</p>
                    <ul v-else class="space-y-2">
                        <li v-for="entry in activityEntries" :key="entry.id" class="flex items-start gap-3 text-xs">
                            <span class="shrink-0 w-2 h-2 rounded-full bg-accent-500 mt-1.5" />
                            <div class="flex-1 min-w-0">
                                <p class="text-primary">
                                    <span class="font-medium">{{ entry.userName ?? t('backend.projects.activity.system') }}</span>
                                    <span class="text-secondary"> · {{ t(`backend.audit.actions.${entry.module}.${entry.action}`) }}</span>
                                    <span v-if="entry.data?.title" class="text-secondary"> — {{ entry.data.title }}</span>
                                    <span v-if="entry.data?.label" class="text-secondary"> — {{ entry.data.label }}</span>
                                </p>
                                <p class="text-muted">{{ formatRelativeDate(entry.createdAt) }}</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-primary">{{ t('backend.projects.tasks') }}</h3>
                    <AppButton
                        v-if="can('project.tasks.manage')"
                        variant="secondary"
                        size="sm"
                        v-on:click="openCreateTask()"
                    >
                        <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('backend.projects.task.add') }}
                    </AppButton>
                </div>

                <!-- Saved views row -->
                <div v-if="savedViewListPath" class="flex items-center gap-2 flex-wrap">
                    <Bookmark class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" />
                    <button
                        v-for="view in savedViews.views.value"
                        :key="view.id"
                        type="button"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium transition-colors"
                        :class="savedViews.selectedViewId.value === view.id ? 'bg-accent-600/15 text-accent-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
                        v-on:click="savedViews.applyView(view, (filters) => { searchInput = filters.search ?? ''; setStatusFilter(filters.statusFilter ?? ''); onSearch(); })"
                    >
                        {{ view.name }}
                        <X class="w-3 h-3 hover:text-rose-400" :stroke-width="2" v-on:click.stop="savedViews.deleteView(view)" />
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs text-muted hover:text-primary hover:bg-surface-2 transition-colors"
                        v-on:click="savedViews.showSaveModal.value = true"
                    >
                        <Plus class="w-3 h-3" :stroke-width="2" />
                        {{ t('backend.projects.savedViews.save') }}
                    </button>
                </div>

                <!-- Kanban columns with drag&drop — dynamic per project -->
                <div class="flex gap-3 overflow-x-auto pb-2 items-start">
                    <VueDraggable
                        v-model="orderedColumns"
                        :animation="150"
                        handle=".column-drag-handle"
                        class="flex gap-3"
                        v-on:end="persistColumnsOrder"
                    >
                        <div
                            v-for="column in orderedColumns"
                            :key="column.id"
                            class="bg-surface-2 rounded-xl p-3 space-y-2 w-72 shrink-0"
                        >
                            <div
                                class="flex items-center justify-between gap-1"
                                :class="can('project.tasks.manage') ? 'column-drag-handle cursor-grab active:cursor-grabbing' : ''"
                            >
                                <span class="text-xs font-semibold text-secondary uppercase tracking-wide truncate" :title="column.label">
                                    {{ column.label }}
                                </span>
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <AppIconButton
                                        v-if="can('project.tasks.manage')"
                                        color="accent"
                                        :title="t('backend.projects.task.add')"
                                        v-on:click="openCreateTask(column.id)"
                                    >
                                        <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton
                                        v-if="can('project.tasks.manage')"
                                        color="accent"
                                        :title="t('backend.projects.columns.rename')"
                                        v-on:click="openRenameColumn(column)"
                                    >
                                        <Pencil class="w-3 h-3" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton
                                        v-if="can('project.tasks.manage') && (activeProject.columns ?? []).length > 1"
                                        color="rose"
                                        :title="t('backend.projects.columns.delete')"
                                        v-on:click="confirmDeleteColumn(column)"
                                    >
                                        <Trash2 class="w-3 h-3" :stroke-width="2" />
                                    </AppIconButton>
                                </div>
                            </div>

                            <p v-if="!tasksByColumn(column.id).length" class="text-xs text-muted py-2 text-center">{{ t('backend.projects.task.empty') }}</p>

                            <VueDraggable
                                v-model="localColumns[column.id]"
                                :group="{ name: 'tasks', put: true, pull: true }"
                                :animation="150"
                                class="flex flex-col gap-2 min-h-12"
                                v-on:add="() => onColumnAdd(column.id)"
                                v-on:end="(event) => { if (event.from === event.to) onColumnEnd(column.id); }"
                            >
                                <div
                                    v-for="task in localColumns[column.id]"
                                    :key="task.id"
                                    class="bg-surface border border-line/60 rounded-lg p-3 space-y-1.5 shadow-sm cursor-grab active:cursor-grabbing select-none"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-medium text-primary flex-1 min-w-0 break-words">{{ task.title }}</p>
                                        <div v-if="can('project.tasks.manage')" class="flex items-center gap-0.5 shrink-0">
                                            <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click.stop="openEditTask(task)">
                                                <Pencil class="w-3 h-3" :stroke-width="2" />
                                            </AppIconButton>
                                            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click.stop="confirmDeleteTask(task)">
                                                <Trash2 class="w-3 h-3" :stroke-width="2" />
                                            </AppIconButton>
                                        </div>
                                    </div>
                                    <div v-if="(task.labelIds ?? []).length" class="flex flex-wrap gap-1">
                                        <AppBadge
                                            v-for="labelId in task.labelIds"
                                            :key="labelId"
                                            :color="labelsById[labelId]?.color ?? 'slate'"
                                            size="sm"
                                        >
                                            {{ labelsById[labelId]?.name ?? '?' }}
                                        </AppBadge>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5 items-center">
                                        <AppBadge :color="TASK_PRIORITY_TONE[task.priority] ?? 'slate'" size="sm">
                                            {{ task.priorityLabel }}
                                        </AppBadge>
                                        <span v-if="task.storyPoints" class="text-xs text-accent-400 font-medium">{{ task.storyPoints }} {{ t('backend.projects.task.fields.storyPointsShort') }}</span>
                                        <span v-if="task.itemsTotal" class="text-xs text-muted">☑ {{ task.itemsDone }}/{{ task.itemsTotal }}</span>
                                        <span v-if="task.assignee" class="text-xs text-muted">{{ task.assignee.name }}</span>
                                        <span v-if="task.dueDate" class="text-xs text-muted">{{ task.dueDate }}</span>
                                    </div>
                                </div>
                            </VueDraggable>
                        </div>
                    </VueDraggable>

                    <!-- Add column placeholder (outside the draggable list so it stays last) -->
                    <AppButton
                        v-if="can('project.tasks.manage')"
                        variant="dashed"
                        size="md"
                        class="w-72 shrink-0 py-3"
                        v-on:click="openCreateColumn"
                    >
                        <Plus class="w-4 h-4" :stroke-width="2" />
                        {{ t('backend.projects.columns.add') }}
                    </AppButton>
                </div>
            </div>
        </div>

        <!-- Create Project modal -->
        <AppModal :show="showProjectModal" v-on:close="showProjectModal = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('backend.projects.add') }}</h3>
            <div class="space-y-4">
                <AppInput
                    v-model="newProject.title"
                    :label="t('backend.projects.fields.title')"
                    :placeholder="t('backend.projects.placeholders.title')"
                    :required="true"
                    :error="projectCreateErrors.title"
                />
                <AppTextarea
                    v-model="newProject.description"
                    :label="t('backend.projects.fields.description')"
                    :placeholder="t('backend.projects.placeholders.description')"
                    :rows="3"
                />
                <AppSelect v-model="newProject.status" :label="t('backend.projects.fields.status')">
                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </AppSelect>
                <div class="grid grid-cols-2 gap-3">
                    <AppDatePicker v-model="newProject.startDate" :label="t('backend.projects.fields.startDate')" />
                    <AppDatePicker v-model="newProject.endDate" :label="t('backend.projects.fields.endDate')" />
                </div>
                <AppMultiselect
                    v-model="newProject.responsibleUserId"
                    :label="t('backend.projects.fields.responsible')"
                    :placeholder="t('backend.projects.placeholders.responsible')"
                    :options="userOptions"
                    :allow-empty="true"
                />
                <AppMultiselect
                    v-model="newProject.crmContactIds"
                    :label="t('backend.projects.fields.contacts')"
                    :placeholder="t('backend.projects.placeholders.contacts')"
                    :options="contactOptions"
                    :multiple="true"
                    :allow-empty="true"
                />
                <AppMultiselect
                    v-model="newProject.crmCompanyId"
                    :label="t('backend.projects.fields.company')"
                    :placeholder="t('backend.projects.placeholders.company')"
                    :options="companyOptions"
                    :allow-empty="true"
                />
                <AppMultiselect
                    v-model="newProject.crmDealId"
                    :label="t('backend.projects.fields.deal')"
                    :placeholder="t('backend.projects.placeholders.deal')"
                    :options="dealOptions"
                    :allow-empty="true"
                />
            </div>
            <AppModalFooter>
                <AppButton variant="secondary" v-on:click="showProjectModal = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" :loading="projectCreateLoading" v-on:click="submitCreateProject">
                    {{ t('shared.common.create') }}
                </AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Edit Project modal -->
        <AppModal :show="showEditProjectModal" v-on:close="showEditProjectModal = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('shared.common.edit') }}</h3>
            <div class="space-y-4">
                <AppInput
                    v-model="editProjectForm.title"
                    :label="t('backend.projects.fields.title')"
                    :placeholder="t('backend.projects.placeholders.title')"
                    :required="true"
                    :error="projectEditErrors.title"
                />
                <AppTextarea
                    v-model="editProjectForm.description"
                    :label="t('backend.projects.fields.description')"
                    :placeholder="t('backend.projects.placeholders.description')"
                    :rows="3"
                />
                <AppSelect v-model="editProjectForm.status" :label="t('backend.projects.fields.status')">
                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </AppSelect>
                <div class="grid grid-cols-2 gap-3">
                    <AppDatePicker v-model="editProjectForm.startDate" :label="t('backend.projects.fields.startDate')" />
                    <AppDatePicker v-model="editProjectForm.endDate" :label="t('backend.projects.fields.endDate')" />
                </div>
                <AppMultiselect
                    v-model="editProjectForm.responsibleUserId"
                    :label="t('backend.projects.fields.responsible')"
                    :placeholder="t('backend.projects.placeholders.responsible')"
                    :options="userOptions"
                    :allow-empty="true"
                />
                <AppMultiselect
                    v-model="editProjectForm.crmContactIds"
                    :label="t('backend.projects.fields.contacts')"
                    :placeholder="t('backend.projects.placeholders.contacts')"
                    :options="contactOptions"
                    :multiple="true"
                    :allow-empty="true"
                />
                <AppMultiselect
                    v-model="editProjectForm.crmCompanyId"
                    :label="t('backend.projects.fields.company')"
                    :placeholder="t('backend.projects.placeholders.company')"
                    :options="companyOptions"
                    :allow-empty="true"
                />
                <AppMultiselect
                    v-model="editProjectForm.crmDealId"
                    :label="t('backend.projects.fields.deal')"
                    :placeholder="t('backend.projects.placeholders.deal')"
                    :options="dealOptions"
                    :allow-empty="true"
                />
            </div>
            <AppModalFooter>
                <AppButton variant="secondary" v-on:click="showEditProjectModal = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" :loading="projectEditLoading" v-on:click="submitEditProject">
                    {{ t('shared.common.save') }}
                </AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Create Task modal -->
        <AppModal :show="showCreateTask" v-on:close="showCreateTask = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('backend.projects.task.add') }}</h3>
            <div class="space-y-4">
                <AppInput
                    v-model="newTask.title"
                    :label="t('backend.projects.task.fields.title')"
                    :placeholder="t('backend.projects.placeholders.taskTitle')"
                    :required="true"
                    :error="createTaskErrors.title"
                />
                <AppTextarea
                    v-model="newTask.description"
                    :label="t('backend.projects.task.fields.description')"
                    :placeholder="t('backend.projects.placeholders.taskDescription')"
                    :rows="3"
                />
                <div class="grid grid-cols-2 gap-3">
                    <AppSelect v-model="newTask.columnId" :label="t('backend.projects.task.fields.column')">
                        <option v-for="column in (activeProject?.columns ?? [])" :key="column.id" :value="column.id">{{ column.label }}</option>
                    </AppSelect>
                    <AppSelect v-model="newTask.priority" :label="t('backend.projects.task.fields.priority')">
                        <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </AppSelect>
                </div>
                <AppMultiselect
                    v-model="newTask.assigneeId"
                    :label="t('backend.projects.task.fields.assignee')"
                    :placeholder="t('backend.projects.placeholders.assignee')"
                    :options="userOptions"
                    :allow-empty="true"
                />
                <AppMultiselect
                    v-if="labelOptions.length"
                    v-model="newTask.labelIds"
                    :label="t('backend.projects.task.fields.labels')"
                    :options="labelOptions"
                    :multiple="true"
                    :allow-empty="true"
                />
                <div class="grid grid-cols-2 gap-3">
                    <AppDatePicker v-model="newTask.dueDate" :label="t('backend.projects.task.fields.dueDate')" />
                    <AppInput
                        v-model.number="newTask.storyPoints"
                        type="number"
                        min="0"
                        :label="t('backend.projects.task.fields.storyPoints')"
                        :placeholder="t('backend.projects.placeholders.storyPoints')"
                    />
                </div>
                <AppInput
                    v-model.number="newTask.estimateMinutes"
                    type="number"
                    min="0"
                    :label="t('backend.projects.task.fields.estimateMinutes')"
                    :placeholder="t('backend.projects.placeholders.estimateMinutes')"
                />
            </div>
            <AppModalFooter>
                <AppButton variant="secondary" v-on:click="showCreateTask = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" :loading="createTaskLoading" v-on:click="submitCreateTask">
                    {{ t('shared.common.create') }}
                </AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Edit Task modal -->
        <AppModal :show="showEditTask" v-on:close="showEditTask = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('shared.common.edit') }}</h3>
            <div class="space-y-4">
                <AppInput
                    v-model="editTaskForm.title"
                    :label="t('backend.projects.task.fields.title')"
                    :placeholder="t('backend.projects.placeholders.taskTitle')"
                    :required="true"
                    :error="editTaskErrors.title"
                />
                <AppTextarea
                    v-model="editTaskForm.description"
                    :label="t('backend.projects.task.fields.description')"
                    :placeholder="t('backend.projects.placeholders.taskDescription')"
                    :rows="3"
                />
                <div class="grid grid-cols-2 gap-3">
                    <AppSelect v-model="editTaskForm.columnId" :label="t('backend.projects.task.fields.column')">
                        <option v-for="column in (activeProject?.columns ?? [])" :key="column.id" :value="column.id">{{ column.label }}</option>
                    </AppSelect>
                    <AppSelect v-model="editTaskForm.priority" :label="t('backend.projects.task.fields.priority')">
                        <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </AppSelect>
                </div>
                <AppMultiselect
                    v-model="editTaskForm.assigneeId"
                    :label="t('backend.projects.task.fields.assignee')"
                    :placeholder="t('backend.projects.placeholders.assignee')"
                    :options="userOptions"
                    :allow-empty="true"
                />
                <AppMultiselect
                    v-if="labelOptions.length"
                    v-model="editTaskForm.labelIds"
                    :label="t('backend.projects.task.fields.labels')"
                    :options="labelOptions"
                    :multiple="true"
                    :allow-empty="true"
                />
                <div class="grid grid-cols-2 gap-3">
                    <AppDatePicker v-model="editTaskForm.dueDate" :label="t('backend.projects.task.fields.dueDate')" />
                    <AppInput
                        v-model.number="editTaskForm.storyPoints"
                        type="number"
                        min="0"
                        :label="t('backend.projects.task.fields.storyPoints')"
                        :placeholder="t('backend.projects.placeholders.storyPoints')"
                    />
                </div>
                <AppInput
                    v-model.number="editTaskForm.estimateMinutes"
                    type="number"
                    min="0"
                    :label="t('backend.projects.task.fields.estimateMinutes')"
                    :placeholder="t('backend.projects.placeholders.estimateMinutes')"
                />
                <AppSelect
                    v-if="sprintOptions.length"
                    v-model="editTaskForm.sprintId"
                    :label="t('backend.projects.task.fields.sprint')"
                >
                    <option value="">—</option>
                    <option v-for="opt in sprintOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </AppSelect>
                <AppMultiselect
                    v-model="editTaskForm.watcherIds"
                    :label="t('backend.projects.task.fields.watchers')"
                    :options="userOptions"
                    :multiple="true"
                    :allow-empty="true"
                />

                <!-- ── Checklist ─────────────────────────────────────── -->
                <div v-if="editingTask" class="border-t border-line/40 pt-4 space-y-2">
                    <div class="flex items-center gap-2">
                        <CheckSquare class="w-4 h-4 text-secondary" :stroke-width="2" />
                        <h4 class="text-sm font-semibold text-primary">{{ t('backend.projects.task.fields.items') }}</h4>
                        <span v-if="taskExtras.localItems.value.length" class="text-xs text-muted">
                            {{ taskExtras.localItems.value.filter((i) => i.done).length }}/{{ taskExtras.localItems.value.length }}
                        </span>
                    </div>
                    <ul class="space-y-1">
                        <li v-for="(item, index) in taskExtras.localItems.value" :key="index" class="flex items-center gap-2 group">
                            <button type="button" class="shrink-0 text-secondary hover:text-accent-400" v-on:click="taskExtras.toggleItem(item)">
                                <CheckSquare v-if="item.done" class="w-4 h-4 text-accent-400" :stroke-width="2" />
                                <Square v-else class="w-4 h-4" :stroke-width="2" />
                            </button>
                            <span class="text-sm flex-1" :class="item.done ? 'text-muted line-through' : 'text-primary'">{{ item.label }}</span>
                            <AppIconButton color="rose" :title="t('shared.common.delete')" class="opacity-0 group-hover:opacity-100" v-on:click="taskExtras.removeItem(index)">
                                <X class="w-3 h-3" :stroke-width="2" />
                            </AppIconButton>
                        </li>
                    </ul>
                    <div class="flex items-center gap-2">
                        <AppInput
                            v-model="taskExtras.newItemLabel.value"
                            :placeholder="t('backend.projects.task.fields.addItem')"
                            class="flex-1"
                            v-on:keydown.enter.prevent="taskExtras.addItem"
                        />
                        <AppButton variant="ghost" size="sm" v-on:click="taskExtras.addItem">
                            <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                        </AppButton>
                    </div>
                </div>

                <!-- ── Time tracking ────────────────────────────────── -->
                <div v-if="editingTask" class="border-t border-line/40 pt-4 space-y-2">
                    <div class="flex items-center gap-2">
                        <Clock class="w-4 h-4 text-secondary" :stroke-width="2" />
                        <h4 class="text-sm font-semibold text-primary">{{ t('backend.projects.task.fields.loggedMinutes') }}</h4>
                        <span class="text-xs text-muted">
                            {{ Math.floor((editingTask.loggedMinutes ?? 0) / 60) }}h {{ (editingTask.loggedMinutes ?? 0) % 60 }}m
                        </span>
                    </div>
                    <ul v-if="editingTask.timeEntries?.length" class="space-y-1 max-h-32 overflow-y-auto scrollbar-thin">
                        <li v-for="entry in editingTask.timeEntries ?? []" :key="entry.id" class="flex items-center gap-2 text-xs">
                            <span class="font-medium text-primary">{{ entry.minutes }}m</span>
                            <span class="text-muted">{{ entry.userName }} · {{ entry.loggedAt }}</span>
                            <span v-if="entry.note" class="text-secondary truncate flex-1">— {{ entry.note }}</span>
                            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="taskExtras.deleteTimeEntry(entry)">
                                <X class="w-3 h-3" :stroke-width="2" />
                            </AppIconButton>
                        </li>
                    </ul>
                    <div class="flex items-center gap-2">
                        <AppInput
                            v-model.number="taskExtras.newTimeEntry.value.minutes"
                            type="number"
                            min="1"
                            :placeholder="t('backend.projects.task.fields.estimateMinutes')"
                            class="w-24"
                        />
                        <AppInput
                            v-model="taskExtras.newTimeEntry.value.note"
                            :placeholder="t('backend.projects.task.timeEntryNote')"
                            class="flex-1"
                        />
                        <AppButton variant="ghost" size="sm" v-on:click="taskExtras.logTime">
                            <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                        </AppButton>
                    </div>
                </div>

                <!-- ── Attachments ──────────────────────────────────── -->
                <div v-if="editingTask" class="border-t border-line/40 pt-4 space-y-2">
                    <div class="flex items-center gap-2">
                        <Paperclip class="w-4 h-4 text-secondary" :stroke-width="2" />
                        <h4 class="text-sm font-semibold text-primary">{{ t('backend.projects.task.fields.attachments') }}</h4>
                        <span v-if="editingTask.attachments?.length" class="text-xs text-muted">{{ editingTask.attachments.length }}</span>
                    </div>
                    <ul v-if="editingTask.attachments?.length" class="space-y-1">
                        <li v-for="media in editingTask.attachments" :key="media.id" class="flex items-center gap-2 text-xs group">
                            <Paperclip class="w-3 h-3 text-muted shrink-0" :stroke-width="2" />
                            <a :href="media.url" target="_blank" rel="noopener" class="text-accent-400 hover:underline truncate flex-1">{{ media.name }}</a>
                            <AppIconButton color="rose" :title="t('shared.common.delete')" class="opacity-0 group-hover:opacity-100" v-on:click="taskExtras.detachMedia(media)">
                                <X class="w-3 h-3" :stroke-width="2" />
                            </AppIconButton>
                        </li>
                    </ul>
                    <p class="text-xs text-muted italic">{{ t('backend.projects.task.attachmentsHint') }}</p>
                </div>

                <!-- ── Comments ─────────────────────────────────────── -->
                <div v-if="editingTask" class="border-t border-line/40 pt-4 space-y-2">
                    <div class="flex items-center gap-2">
                        <MessageSquare class="w-4 h-4 text-secondary" :stroke-width="2" />
                        <h4 class="text-sm font-semibold text-primary">{{ t('backend.projects.task.fields.comments') }}</h4>
                        <span v-if="editingTask.commentCount" class="text-xs text-muted">{{ editingTask.commentCount }}</span>
                    </div>
                    <ul v-if="editingTask.comments?.length" class="space-y-2 max-h-48 overflow-y-auto scrollbar-thin">
                        <li v-for="comment in editingTask.comments" :key="comment.id" class="bg-surface-2 rounded-lg p-2 group">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-muted">
                                        <span class="font-medium text-primary">{{ comment.authorName }}</span>
                                        — {{ new Date(comment.createdAt).toLocaleString() }}
                                    </p>
                                    <p class="text-sm text-primary mt-1 whitespace-pre-wrap">{{ comment.content }}</p>
                                </div>
                                <AppIconButton color="rose" :title="t('shared.common.delete')" class="opacity-0 group-hover:opacity-100 shrink-0" v-on:click="taskExtras.deleteComment(comment)">
                                    <X class="w-3 h-3" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </li>
                    </ul>
                    <div class="flex items-start gap-2">
                        <AppTextarea
                            v-model="taskExtras.newCommentContent.value"
                            :rows="2"
                            :placeholder="t('backend.projects.task.commentPlaceholder')"
                            class="flex-1"
                        />
                        <AppButton variant="primary" size="sm" v-on:click="taskExtras.submitComment">
                            {{ t('shared.common.send') }}
                        </AppButton>
                    </div>
                </div>
            </div>
            <AppModalFooter>
                <AppButton variant="secondary" v-on:click="showEditTask = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" :loading="editTaskLoading" v-on:click="submitEditTask">
                    {{ t('shared.common.save') }}
                </AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Delete project confirm -->
        <AppModal :show="!!pendingDeleteProject" max-width="sm" v-on:close="confirmDeleteProject(null)">
            <p class="text-sm text-primary">{{ t('backend.projects.deleteConfirm', { name: pendingDeleteProject?.title ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.projects.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="confirmDeleteProject(null)">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="projectDeleting" v-on:click="doDeleteProject">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Delete task confirm -->
        <AppModal :show="!!pendingDeleteTask" max-width="sm" v-on:close="confirmDeleteTask(null)">
            <p class="text-sm text-primary">{{ t('backend.projects.task.deleteConfirm', { name: pendingDeleteTask?.title ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.projects.task.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="confirmDeleteTask(null)">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="taskDeleting" v-on:click="doDeleteTask">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Create column modal -->
        <AppModal :show="showCreateColumn" max-width="sm" v-on:close="showCreateColumn = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('backend.projects.columns.add') }}</h3>
            <div class="space-y-4">
                <AppInput
                    v-model="newColumn.label"
                    :label="t('backend.projects.task.fields.column')"
                    :placeholder="t('backend.projects.columns.addPlaceholder')"
                    :required="true"
                    :error="createColumnErrors.label"
                />
            </div>
            <AppModalFooter>
                <AppButton variant="secondary" v-on:click="showCreateColumn = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" :loading="createColumnLoading" v-on:click="submitCreateColumn">{{ t('shared.common.create') }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Rename column modal -->
        <AppModal :show="showRenameColumn" max-width="sm" v-on:close="showRenameColumn = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('backend.projects.columns.rename') }}</h3>
            <div class="space-y-4">
                <AppInput
                    v-model="renameForm.label"
                    :label="t('backend.projects.task.fields.column')"
                    :placeholder="t('backend.projects.columns.addPlaceholder')"
                    :required="true"
                    :error="renameErrors.label"
                />
            </div>
            <AppModalFooter>
                <AppButton variant="secondary" v-on:click="showRenameColumn = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" :loading="renameLoading" v-on:click="submitRenameColumn">{{ t('shared.common.save') }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Delete column confirm -->
        <AppModal :show="!!pendingDeleteColumn" max-width="sm" v-on:close="pendingDeleteColumn = null">
            <p class="text-sm text-primary">{{ t('backend.projects.columns.deleteConfirm', { label: pendingDeleteColumn?.label ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.projects.columns.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingDeleteColumn = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteColumnLoading" v-on:click="doDeleteColumn">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Labels manager -->
        <AppModal :show="labelsManage.showLabelsModal.value" max-width="md" v-on:close="labelsManage.showLabelsModal.value = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('backend.projects.labels.manage') }}</h3>
            <div class="space-y-4">
                <ul v-if="(activeProject?.labels ?? []).length" class="space-y-1">
                    <li
                        v-for="label in (activeProject?.labels ?? [])"
                        :key="label.id"
                        class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-surface-2 group"
                    >
                        <AppBadge :color="label.color" size="sm">{{ label.name }}</AppBadge>
                        <span class="flex-1" />
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="labelsManage.startEdit(label)">
                            <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="labelsManage.deleteLabel(label)">
                            <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        </AppIconButton>
                    </li>
                </ul>
                <div class="border-t border-line/40 pt-4 space-y-3">
                    <p class="text-xs font-semibold text-secondary uppercase tracking-wide">
                        {{ labelsManage.editingLabel.value ? t('backend.projects.labels.edit') : t('backend.projects.labels.add') }}
                    </p>
                    <AppInput
                        v-model="labelsManage.labelForm.value.name"
                        :label="t('backend.projects.labels.nameField')"
                        :placeholder="t('backend.projects.labels.namePlaceholder')"
                        :error="labelsManage.labelErrors.value.name ?? ''"
                    />
                    <div>
                        <p class="text-xs text-secondary mb-1">{{ t('backend.projects.labels.colorField') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="color in LABEL_COLORS"
                                :key="color"
                                type="button"
                                class="px-2 py-1 rounded-md border-2 transition-colors"
                                :class="labelsManage.labelForm.value.color === color ? 'border-accent-500' : 'border-transparent'"
                                v-on:click="labelsManage.labelForm.value.color = color"
                            >
                                <AppBadge :color="color" size="sm">{{ color }}</AppBadge>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <AppModalFooter>
                <AppButton v-if="labelsManage.editingLabel.value" variant="ghost" size="md" v-on:click="labelsManage.cancelEdit">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" size="md" :loading="labelsManage.loading.value" v-on:click="labelsManage.submitLabel">
                    {{ labelsManage.editingLabel.value ? t('shared.common.save') : t('shared.common.create') }}
                </AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Sprints manager -->
        <AppModal :show="sprintsManage.showSprintsModal.value" max-width="md" v-on:close="sprintsManage.showSprintsModal.value = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('backend.projects.sprints.manage') }}</h3>
            <div class="space-y-4">
                <ul v-if="(activeProject?.sprints ?? []).length" class="space-y-1">
                    <li
                        v-for="sprint in (activeProject?.sprints ?? [])"
                        :key="sprint.id"
                        class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-surface-2 group"
                    >
                        <span class="text-sm font-medium text-primary">{{ sprint.name }}</span>
                        <AppBadge v-if="sprint.isActive" color="emerald" size="sm">{{ t('backend.projects.sprints.active') }}</AppBadge>
                        <span class="text-xs text-muted">{{ sprint.startDate ?? '?' }} → {{ sprint.endDate ?? '?' }}</span>
                        <span class="text-xs text-muted">· {{ sprint.taskCount }} {{ t('backend.projects.tasks').toLowerCase() }}</span>
                        <span class="flex-1" />
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="sprintsManage.startEdit(sprint)">
                            <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="sprintsManage.deleteSprint(sprint)">
                            <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        </AppIconButton>
                    </li>
                </ul>
                <div class="border-t border-line/40 pt-4 space-y-3">
                    <p class="text-xs font-semibold text-secondary uppercase tracking-wide">
                        {{ sprintsManage.editingSprint.value ? t('backend.projects.sprints.edit') : t('backend.projects.sprints.add') }}
                    </p>
                    <AppInput v-model="sprintsManage.sprintForm.value.name" :label="t('backend.projects.sprints.nameField')" :placeholder="t('backend.projects.sprints.namePlaceholder')" :error="sprintsManage.sprintErrors.value.name ?? ''" />
                    <div class="grid grid-cols-2 gap-3">
                        <AppDatePicker v-model="sprintsManage.sprintForm.value.startDate" :label="t('backend.projects.fields.startDate')" />
                        <AppDatePicker v-model="sprintsManage.sprintForm.value.endDate" :label="t('backend.projects.fields.endDate')" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-primary">
                        <input v-model="sprintsManage.sprintForm.value.isActive" type="checkbox" class="rounded border-line">
                        {{ t('backend.projects.sprints.activeField') }}
                    </label>
                </div>
            </div>
            <AppModalFooter>
                <AppButton v-if="sprintsManage.editingSprint.value" variant="ghost" size="md" v-on:click="sprintsManage.cancelEdit">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" size="md" :loading="sprintsManage.loading.value" v-on:click="sprintsManage.submitSprint">
                    {{ sprintsManage.editingSprint.value ? t('shared.common.save') : t('shared.common.create') }}
                </AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Save view modal -->
        <AppModal :show="savedViews.showSaveModal.value" max-width="sm" v-on:close="savedViews.showSaveModal.value = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('backend.projects.savedViews.save') }}</h3>
            <div class="space-y-4">
                <AppInput v-model="savedViews.newViewName.value" :label="t('backend.projects.savedViews.nameField')" :placeholder="t('backend.projects.savedViews.namePlaceholder')" />
            </div>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="savedViews.showSaveModal.value = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" size="md" v-on:click="savedViews.saveView({ statusFilter, search: searchInput })">
                    {{ t('shared.common.save') }}
                </AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
