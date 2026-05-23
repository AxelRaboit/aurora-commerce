<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { ScrollText, Plus, Pencil, Send, Archive, Copy, Trash2, X } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppTextarea from "@/shared/components/form/input/AppTextarea.vue";
import { useTemplateStatus } from "@welding/backend/composables/useWeldingStatus.js";
import { useWorkflowTemplatesList } from "./composables/useWorkflowTemplatesList.js";

const props = defineProps({
    workflowTemplates: { type: Array, default: () => [] },
});

const { t } = useI18n();
const items = ref([...props.workflowTemplates]);
const query = ref("");

const { BADGE: STATUS_BADGE } = useTemplateStatus();

const filteredItems = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return items.value;
    return items.value.filter((tpl) =>
        (tpl.title ?? "").toLowerCase().includes(q)
        || (tpl.applicableTo ?? "").toLowerCase().includes(q),
    );
});

const {
    loading: requestLoading,
    createOpen,
    form,
    errors: formErrors,
    openCreate,
    submitCreate,
    pendingPublish,
    doPublish,
    pendingClone,
    doClone,
    pendingArchive,
    doArchive,
    pendingDelete,
    doDelete,
} = useWorkflowTemplatesList(items);
</script>

<template>
    <div class="p-4 sm:p-6 space-y-4">
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-accent-100 dark:bg-accent-900/30">
                <ScrollText class="w-6 h-6 text-accent-500" :stroke-width="1.5" />
            </div>
            <div>
                <h1 class="text-xl font-semibold text-primary">{{ t("welding.workflow_templates.title") }}</h1>
                <p class="text-sm text-secondary">{{ t("welding.workflow_templates.subtitle") }}</p>
            </div>
        </div>

        <AppListToolbar>
            <AppSearchInput
                v-model="query"
                :placeholder="t('welding.workflow_templates.search_placeholder')"
            />
            <template #actions>
                <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="openCreate">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("welding.workflow_templates.new") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <!-- Mobile card layout -->
        <div class="sm:hidden space-y-3">
            <div
                v-for="template in filteredItems"
                :key="template.id"
                class="bg-surface border border-line rounded-lg p-4 space-y-3"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium text-primary truncate">{{ template.title }}</span>
                        <span class="text-xs text-muted">v{{ template.version }}</span>
                        <span :class="['text-xs px-2 py-0.5 rounded-full', STATUS_BADGE[template.status]]">
                            {{ t("welding.workflow_templates.status_" + template.status) }}
                        </span>
                    </div>
                    <p class="text-xs text-secondary">
                        {{ template.applicableTo || "—" }} ·
                        {{ template.stepsCount }} {{ t("welding.workflow_templates.steps") }}
                    </p>
                </div>
                <div class="flex items-center justify-end gap-0.5 pt-2 border-t border-line">
                    <AppIconButton color="accent" :title="t('welding.workflow_templates.open')" :href="`/backend/welding/workflow-templates/${template.id}/editor`">
                        <Pencil class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton v-if="template.status === 'draft'" color="emerald" :title="t('welding.workflow_templates.publish')" v-on:click="pendingPublish = template">
                        <Send class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton v-if="template.status === 'published'" color="sky" :title="t('welding.workflow_templates.clone')" v-on:click="pendingClone = template">
                        <Copy class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton v-if="template.status !== 'archived'" color="amber" :title="t('welding.workflow_templates.archive')" v-on:click="pendingArchive = template">
                        <Archive class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton color="rose" :title="t('welding.workflow_templates.delete')" v-on:click="pendingDelete = template">
                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                </div>
            </div>
        </div>

        <!-- Desktop table -->
        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("welding.workflow_templates.field_title") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("welding.workflow_templates.field_applicable_to") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("welding.workflow_templates.col_status") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("welding.workflow_templates.col_steps") }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr
                        v-for="template in filteredItems"
                        :key="template.id"
                        class="group hover:bg-surface-2/40 transition-colors"
                    >
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-medium text-primary truncate">{{ template.title }}</span>
                                <span class="text-xs text-muted">v{{ template.version }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ template.applicableTo || "—" }}</td>
                        <td class="px-6 py-3 hidden lg:table-cell">
                            <span :class="['text-xs px-2 py-0.5 rounded-full', STATUS_BADGE[template.status]]">
                                {{ t("welding.workflow_templates.status_" + template.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-secondary hidden lg:table-cell">
                            {{ template.stepsCount }}
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="accent" :title="t('welding.workflow_templates.open')" :href="`/backend/welding/workflow-templates/${template.id}/editor`">
                                    <Pencil class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="template.status === 'draft'" color="emerald" :title="t('welding.workflow_templates.publish')" v-on:click="publish(template)">
                                    <Send class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="template.status === 'published'" color="sky" :title="t('welding.workflow_templates.clone')" v-on:click="clone(template)">
                                    <Copy class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="template.status !== 'archived'" color="amber" :title="t('welding.workflow_templates.archive')" v-on:click="archive(template)">
                                    <Archive class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton color="rose" :title="t('welding.workflow_templates.delete')" v-on:click="confirmDelete(template)">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="items.length === 0">
                        <td :colspan="5" class="px-6 py-8 text-center text-sm text-muted">{{ t("welding.workflow_templates.empty") }}</td>
                    </tr>
                    <tr v-else-if="filteredItems.length === 0">
                        <td :colspan="5" class="px-6 py-8 text-center text-sm text-muted">{{ t("welding.workflow_templates.search_no_match") }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-if="items.length === 0" class="sm:hidden py-8 text-center text-sm text-muted">{{ t("welding.workflow_templates.empty") }}</p>
        <p v-else-if="filteredItems.length === 0" class="sm:hidden py-8 text-center text-sm text-muted">{{ t("welding.workflow_templates.search_no_match") }}</p>

        <AppModal
            :show="createOpen"
            max-width="lg"
            :title="t('welding.workflow_templates.new')"
            :icon="ScrollText"
            :close-on-overlay="false"
            v-on:close="createOpen = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="form.title"
                    :label="t('welding.workflow_templates.field_title')"
                    :placeholder="t('welding.workflow_templates.field_title_placeholder')"
                    :error="formErrors.title"
                    required
                />
                <AppTextarea
                    v-model="form.description"
                    :label="t('welding.workflow_templates.field_description')"
                    :placeholder="t('welding.workflow_templates.field_description_placeholder')"
                    :rows="3"
                    :error="formErrors.description"
                />
                <AppInput
                    v-model="form.applicableTo"
                    :label="t('welding.workflow_templates.field_applicable_to')"
                    :placeholder="t('welding.workflow_templates.field_applicable_to_placeholder')"
                    :error="formErrors.applicableTo"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="createOpen = false">
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        :loading="requestLoading"
                        :disabled="requestLoading"
                        v-on:click="submitCreate"
                    >
                        {{ t("welding.workflow_templates.create_and_open") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Publish confirmation -->
        <AppModal
            :show="pendingPublish !== null"
            max-width="sm"
            :title="pendingPublish ? t('welding.workflow_templates.confirm_publish', { title: pendingPublish.title }) : ''"
            :icon="Send"
            :closeable="false"
            v-on:close="pendingPublish = null"
        >
            <p class="text-sm text-secondary">{{ t("welding.workflow_templates.confirm_publish_warning") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingPublish = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        :loading="requestLoading"
                        :disabled="requestLoading"
                        v-on:click="doPublish"
                    >
                        <Send class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("welding.workflow_templates.publish") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Clone confirmation -->
        <AppModal
            :show="pendingClone !== null"
            max-width="sm"
            :title="pendingClone ? t('welding.workflow_templates.confirm_clone', { title: pendingClone.title }) : ''"
            :icon="Copy"
            :closeable="false"
            v-on:close="pendingClone = null"
        >
            <p class="text-sm text-secondary">{{ t("welding.workflow_templates.confirm_clone_warning") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingClone = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        :loading="requestLoading"
                        :disabled="requestLoading"
                        v-on:click="doClone"
                    >
                        <Copy class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("welding.workflow_templates.clone") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Archive confirmation -->
        <AppModal
            :show="pendingArchive !== null"
            max-width="sm"
            :title="pendingArchive ? t('welding.workflow_templates.confirm_archive', { title: pendingArchive.title }) : ''"
            :icon="Archive"
            :closeable="false"
            v-on:close="pendingArchive = null"
        >
            <p class="text-sm text-secondary">{{ t("welding.workflow_templates.confirm_archive_warning") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingArchive = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        :loading="requestLoading"
                        :disabled="requestLoading"
                        v-on:click="doArchive"
                    >
                        <Archive class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("welding.workflow_templates.archive") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Delete confirmation -->
        <AppModal
            :show="pendingDelete !== null"
            max-width="sm"
            :title="t('welding.workflow_templates.delete')"
            :icon="Trash2"
            :closeable="false"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">{{ t("welding.workflow_templates.confirm_delete") }}</p>
            <p class="text-sm text-secondary">{{ pendingDelete?.title }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="danger"
                        size="md"
                        :loading="requestLoading"
                        :disabled="requestLoading"
                        v-on:click="doDelete"
                    >
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("welding.workflow_templates.confirm_delete_button") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
