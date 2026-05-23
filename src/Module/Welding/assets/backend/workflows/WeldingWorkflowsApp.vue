<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { ClipboardCheck, Plus, ExternalLink } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useWorkflowStatus } from "@welding/backend/composables/useWeldingStatus.js";
import { useWorkflowsList } from "./composables/useWorkflowsList.js";

const { t } = useI18n();
const { formatDateTime } = useDateFormat();
const { ORDER: STATUS_ORDER, COLOR: STATUS_COLOR } = useWorkflowStatus();

const statusOptions = computed(() => [
    { value: "", label: t("welding.workflows.filter_all") },
    ...STATUS_ORDER.map((s) => ({ value: s, label: t("welding.workflows.status_" + s) })),
]);

const {
    items,
    listLoading,
    page,
    totalPages,
    total,
    search: query,
    onSearch,
    goToPage,
    statusFilter,
    startOpen,
    publishedTemplates,
    employeeOptions,
    startForm,
    optionsLoading,
    createLoading,
    openStart,
    submitStart,
} = useWorkflowsList();
</script>

<template>
    <div class="p-4 sm:p-6 space-y-4">
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-accent-100 dark:bg-accent-900/30">
                <ClipboardCheck class="w-6 h-6 text-accent-500" :stroke-width="1.5" />
            </div>
            <div>
                <h1 class="text-xl font-semibold text-primary">{{ t("welding.workflows.title") }}</h1>
                <p class="text-sm text-secondary">{{ t("welding.workflows.subtitle") }}</p>
            </div>
        </div>

        <AppListToolbar>
            <div class="grid grid-cols-1 sm:grid-cols-[1fr_220px] gap-2">
                <AppSearchInput
                    :model-value="query"
                    :placeholder="t('welding.workflows.search_placeholder')"
                    v-on:update:model-value="onSearch"
                />
                <AppMultiselect
                    v-model="statusFilter"
                    :options="statusOptions"
                    :allow-empty="false"
                    :searchable="false"
                    :placeholder="t('welding.workflows.filter_status')"
                />
            </div>
            <template #actions>
                <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="openStart">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("welding.workflows.new") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <!-- Mobile card layout -->
        <div class="sm:hidden space-y-3">
            <div
                v-for="workflow in items"
                :key="workflow.id"
                class="bg-surface border border-line rounded-lg p-4 space-y-3"
            >
                <div class="space-y-1">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-xs text-secondary">{{ workflow.reference }}</span>
                        <span :class="['text-xs px-2 py-0.5 rounded-full', STATUS_COLOR[workflow.status]]">
                            {{ t("welding.workflows.status_" + workflow.status) }}
                        </span>
                    </div>
                    <p class="text-sm font-medium text-primary truncate">
                        {{ workflow.templateTitle || "—" }}
                        <span class="text-xs text-muted">v{{ workflow.templateVersion }}</span>
                    </p>
                    <p class="text-xs text-secondary truncate">
                        {{ workflow.assigneeName || t("welding.workflows.no_assignee") }}
                    </p>
                </div>
                <div class="flex items-center justify-end gap-0.5 pt-2 border-t border-line">
                    <AppIconButton color="accent" :title="t('welding.runner.open')" :href="`/backend/welding/workflows/${workflow.id}/runner`">
                        <ExternalLink class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                </div>
            </div>
        </div>

        <!-- Desktop table -->
        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("welding.workflows.col_reference") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("welding.workflows.col_template") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("welding.workflows.col_assignee") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("welding.workflows.col_status") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("welding.workflows.col_created_at") }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr
                        v-for="workflow in items"
                        :key="workflow.id"
                        class="group hover:bg-surface-2/40 transition-colors"
                    >
                        <td class="px-6 py-3 font-mono text-xs text-secondary">{{ workflow.reference }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-primary truncate">{{ workflow.templateTitle || "—" }}</span>
                                <span class="text-xs text-muted">v{{ workflow.templateVersion }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">
                            {{ workflow.assigneeName || t("welding.workflows.no_assignee") }}
                        </td>
                        <td class="px-6 py-3">
                            <span :class="['text-xs px-2 py-0.5 rounded-full', STATUS_COLOR[workflow.status]]">
                                {{ t("welding.workflows.status_" + workflow.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-secondary hidden lg:table-cell">
                            {{ formatDateTime(workflow.createdAt) }}
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="accent" :title="t('welding.runner.open')" :href="`/backend/welding/workflows/${workflow.id}/runner`">
                                    <ExternalLink class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="listLoading && items.length === 0">
                        <td :colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t("welding.workflows.loading") }}</td>
                    </tr>
                    <tr v-else-if="items.length === 0 && total === 0 && !query && !statusFilter">
                        <td :colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t("welding.workflows.empty") }}</td>
                    </tr>
                    <tr v-else-if="items.length === 0">
                        <td :colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t("welding.workflows.search_no_match") }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-if="listLoading && items.length === 0" class="sm:hidden py-8 text-center text-sm text-muted">{{ t("welding.workflows.loading") }}</p>
        <p v-else-if="items.length === 0 && total === 0 && !query && !statusFilter" class="sm:hidden py-8 text-center text-sm text-muted">{{ t("welding.workflows.empty") }}</p>
        <p v-else-if="items.length === 0" class="sm:hidden py-8 text-center text-sm text-muted">{{ t("welding.workflows.search_no_match") }}</p>

        <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />

        <AppModal
            :show="startOpen"
            max-width="lg"
            :title="t('welding.workflows.new')"
            :icon="ClipboardCheck"
            :close-on-overlay="false"
            v-on:close="startOpen = false"
        >
            <div v-if="optionsLoading" class="text-sm text-secondary">{{ t("welding.workflows.loading_options") }}</div>
            <form v-else class="space-y-4" v-on:submit.prevent="submitStart">
                <AppMultiselect
                    v-model="startForm.templateId"
                    :options="publishedTemplates"
                    :label="t('welding.workflows.field_template')"
                    :placeholder="t('welding.workflows.field_template_placeholder')"
                    required
                />
                <p v-if="publishedTemplates.length === 0" class="text-xs text-amber-600 dark:text-amber-400">
                    {{ t("welding.workflows.no_published_template") }}
                </p>
                <AppMultiselect
                    v-model="startForm.assigneeId"
                    :options="employeeOptions"
                    :label="t('welding.workflows.field_assignee')"
                    :placeholder="t('welding.workflows.no_assignee_option')"
                    allow-empty
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="startOpen = false">
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        :loading="createLoading"
                        :disabled="optionsLoading || createLoading || publishedTemplates.length === 0"
                        v-on:click="submitStart"
                    >
                        {{ t("welding.workflows.create_and_open") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
