<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { VueDraggable } from "vue-draggable-plus";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useProjectsListPage } from "./composables/useProjectsListPage.js";
import { useProjectsCreate } from "./composables/useProjectsCreate.js";
import { useProjectsEdit } from "./composables/useProjectsEdit.js";
import { useProjectsDelete } from "./composables/useProjectsDelete.js";
import { useProjectDetail } from "./composables/useProjectDetail.js";
import { useTasksCreate } from "./composables/useTasksCreate.js";
import { useTasksEdit } from "./composables/useTasksEdit.js";
import { useTasksKanban } from "./composables/useTasksKanban.js";
import { useColumnsManage } from "./composables/useColumnsManage.js";
import { useProjectFormOptions } from "./composables/useProjectFormOptions.js";
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
import { Plus, Pencil, Trash2 } from "lucide-vue-next";

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
    statusOptions: { type: Array, default: () => [] },
    priorityOptions: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    crmContacts: { type: Array, default: () => [] },
    crmCompanies: { type: Array, default: () => [] },
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

const { userOptions, contactOptions, companyOptions } = useProjectFormOptions(props);
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
                            <span v-if="activeProject.crmContacts && activeProject.crmContacts.length">{{ t('backend.projects.fields.contactsLabel') }} {{ activeProject.crmContacts.map((c) => c.name).join(', ') }}</span>
                            <span v-if="activeProject.crmCompany">{{ t('backend.projects.fields.companyLabel') }} {{ activeProject.crmCompany.name }}</span>
                        </div>
                    </div>
                    <div v-if="can('project.projects.edit') || can('project.projects.delete')" class="flex items-center gap-0.5 shrink-0">
                        <AppIconButton v-if="can('project.projects.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEditProject(activeProject)">
                            <Pencil class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton v-if="can('project.projects.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDeleteProject(activeProject)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
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

                <!-- Kanban columns with drag&drop — dynamic per project -->
                <div class="flex gap-3 overflow-x-auto pb-2">
                    <div
                        v-for="column in (activeProject.columns ?? [])"
                        :key="column.id"
                        class="bg-surface-2 rounded-xl p-3 space-y-2 w-72 shrink-0"
                    >
                        <div class="flex items-center justify-between gap-1">
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
                                <div class="flex flex-wrap gap-1.5 items-center">
                                    <AppBadge :color="TASK_PRIORITY_TONE[task.priority] ?? 'slate'" size="sm">
                                        {{ task.priorityLabel }}
                                    </AppBadge>
                                    <span v-if="task.assignee" class="text-xs text-muted">{{ task.assignee.name }}</span>
                                    <span v-if="task.dueDate" class="text-xs text-muted">{{ task.dueDate }}</span>
                                </div>
                            </div>
                        </VueDraggable>
                    </div>

                    <!-- Add column placeholder -->
                    <button
                        v-if="can('project.tasks.manage')"
                        type="button"
                        class="w-72 shrink-0 rounded-xl border-2 border-dashed border-line text-secondary hover:bg-surface-2 hover:text-primary transition-colors flex items-center justify-center gap-2 py-3 text-sm"
                        v-on:click="openCreateColumn"
                    >
                        <Plus class="w-4 h-4" :stroke-width="2" />
                        {{ t('backend.projects.columns.add') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Project modal -->
    <AppModal :show="showProjectModal" v-on:close="showProjectModal = false">
        <h3 class="text-lg font-semibold text-primary">{{ t('backend.projects.add') }}</h3>
        <div class="space-y-4 p-4">
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
        <div class="space-y-4 p-4">
            <AppInput
                v-model="editProjectForm.title"
                :label="t('backend.projects.fields.title')"
                :required="true"
                :error="projectEditErrors.title"
            />
            <AppTextarea
                v-model="editProjectForm.description"
                :label="t('backend.projects.fields.description')"
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
                :options="userOptions"
                :allow-empty="true"
            />
            <AppMultiselect
                v-model="editProjectForm.crmContactIds"
                :label="t('backend.projects.fields.contacts')"
                :options="contactOptions"
                :multiple="true"
                :allow-empty="true"
            />
            <AppMultiselect
                v-model="editProjectForm.crmCompanyId"
                :label="t('backend.projects.fields.company')"
                :options="companyOptions"
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
        <div class="space-y-4 p-4">
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
            <AppDatePicker v-model="newTask.dueDate" :label="t('backend.projects.task.fields.dueDate')" />
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
        <div class="space-y-4 p-4">
            <AppInput
                v-model="editTaskForm.title"
                :label="t('backend.projects.task.fields.title')"
                :required="true"
                :error="editTaskErrors.title"
            />
            <AppTextarea
                v-model="editTaskForm.description"
                :label="t('backend.projects.task.fields.description')"
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
                :options="userOptions"
                :allow-empty="true"
            />
            <AppDatePicker v-model="editTaskForm.dueDate" :label="t('backend.projects.task.fields.dueDate')" />
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
        <div class="space-y-4 p-4">
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
        <div class="space-y-4 p-4">
            <AppInput
                v-model="renameForm.label"
                :label="t('backend.projects.task.fields.column')"
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
</template>
