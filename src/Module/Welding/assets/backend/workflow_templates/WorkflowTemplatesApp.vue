<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { ScrollText, Plus, Pencil, Send, Archive, Copy, Trash2 } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { useTemplateStatus } from "@welding/backend/composables/useWeldingStatus.js";

const props = defineProps({
    workflowTemplates: { type: Array, default: () => [] },
});

const { t } = useI18n();
const items = ref([...props.workflowTemplates]);

const { BADGE: STATUS_BADGE } = useTemplateStatus();

const createOpen = ref(false);
const form = ref({ title: "", description: "", applicableTo: "" });
const formErrors = ref({});

const { loading: requestLoading, request } = useRequest();

function openCreate() {
    form.value = { title: "", description: "", applicableTo: "" };
    formErrors.value = {};
    createOpen.value = true;
}

async function submitCreate() {
    formErrors.value = {};
    const data = await request("/backend/welding/workflow-templates", form.value);
    if (!data) return;
    if (data.success) {
        toast.success(t("welding.workflow_templates.created"));
        window.location.href = `/backend/welding/workflow-templates/${data.workflowTemplate.id}/editor`;
        return;
    }
    if (data.errors) formErrors.value = translateServerErrors(t, data.errors);
}

function updateLocal(updated) {
    const idx = items.value.findIndex((x) => x.id === updated.id);
    if (idx !== -1) items.value[idx] = { ...items.value[idx], ...updated };
}

async function publish(template) {
    const data = await request(`/backend/welding/workflow-templates/${template.id}/publish`, {});
    if (data?.success) {
        updateLocal(data.workflowTemplate);
        toast.success(t("welding.workflow_templates.published"));
    }
}

async function archive(template) {
    const data = await request(`/backend/welding/workflow-templates/${template.id}/archive`, {});
    if (data?.success) {
        updateLocal(data.workflowTemplate);
        toast.success(t("welding.workflow_templates.archived"));
    }
}

async function clone(template) {
    const data = await request(`/backend/welding/workflow-templates/${template.id}/clone`, {});
    if (data?.success) {
        toast.success(t("welding.workflow_templates.cloned"));
        window.location.href = `/backend/welding/workflow-templates/${data.workflowTemplate.id}/editor`;
    }
}

const pendingDelete = ref(null);

function confirmDelete(template) {
    pendingDelete.value = template;
}

async function doDelete() {
    if (!pendingDelete.value) return;
    const data = await request(`/backend/welding/workflow-templates/${pendingDelete.value.id}/delete`, {});
    if (data?.success) {
        items.value = items.value.filter((x) => x.id !== pendingDelete.value.id);
        toast.success(t("welding.workflow_templates.deleted"));
        pendingDelete.value = null;
    }
}
</script>

<template>
    <div class="p-4 sm:p-6 space-y-6">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-accent-100 dark:bg-accent-900/30">
                    <ScrollText class="w-6 h-6 text-accent-500" :stroke-width="1.5" />
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-primary">{{ t("welding.workflow_templates.title") }}</h1>
                    <p class="text-sm text-secondary">{{ t("welding.workflow_templates.subtitle") }}</p>
                </div>
            </div>
            <AppButton variant="primary" v-on:click="openCreate">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t("welding.workflow_templates.new") }}
            </AppButton>
        </div>

        <div v-if="items.length === 0" class="rounded-xl border border-line bg-surface p-6 text-sm text-secondary text-center">
            {{ t("welding.workflow_templates.empty") }}
        </div>
        <ul v-else class="space-y-2">
            <li
                v-for="template in items"
                :key="template.id"
                class="rounded-lg border border-line bg-surface p-4 flex flex-wrap items-center gap-4"
            >
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium text-primary truncate">{{ template.title }}</span>
                        <span class="text-xs text-muted">v{{ template.version }}</span>
                        <span :class="['text-xs px-2 py-0.5 rounded-full', STATUS_BADGE[template.status]]">
                            {{ t("welding.workflow_templates.status_" + template.status) }}
                        </span>
                    </div>
                    <div class="text-xs text-secondary mt-0.5">
                        {{ template.applicableTo || "—" }} ·
                        {{ template.stepsCount }} {{ t("welding.workflow_templates.steps") }}
                    </div>
                </div>
                <div class="flex flex-wrap gap-1">
                    <AppButton
                        variant="ghost"
                        size="sm"
                        :href="`/backend/welding/workflow-templates/${template.id}/editor`"
                        :aria-label="t('welding.workflow_templates.open')"
                    >
                        <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("welding.workflow_templates.open") }}
                    </AppButton>
                    <AppButton
                        v-if="template.status === 'draft'"
                        variant="ghost"
                        size="sm"
                        :loading="requestLoading"
                        :disabled="requestLoading"
                        v-on:click="publish(template)"
                    >
                        <Send class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("welding.workflow_templates.publish") }}
                    </AppButton>
                    <AppButton
                        v-if="template.status === 'published'"
                        variant="ghost"
                        size="sm"
                        :loading="requestLoading"
                        :disabled="requestLoading"
                        v-on:click="clone(template)"
                    >
                        <Copy class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("welding.workflow_templates.clone") }}
                    </AppButton>
                    <AppButton
                        v-if="template.status !== 'archived'"
                        variant="ghost"
                        size="sm"
                        :loading="requestLoading"
                        :disabled="requestLoading"
                        v-on:click="archive(template)"
                    >
                        <Archive class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("welding.workflow_templates.archive") }}
                    </AppButton>
                    <AppButton
                        variant="ghost"
                        size="sm"
                        :aria-label="t('welding.workflow_templates.delete')"
                        v-on:click="confirmDelete(template)"
                    >
                        <Trash2 class="w-3.5 h-3.5 text-rose-500" :stroke-width="2" />
                    </AppButton>
                </div>
            </li>
        </ul>

        <AppModal :show="createOpen" :title="t('welding.workflow_templates.new')" v-on:close="createOpen = false">
            <div class="space-y-3">
                <div>
                    <label for="tplTitle" class="block text-xs font-medium text-secondary mb-1">
                        {{ t("welding.workflow_templates.field_title") }} *
                    </label>
                    <input id="tplTitle" v-model="form.title" type="text" class="w-full rounded border border-line bg-surface p-2 text-sm" />
                    <p v-if="formErrors.title" class="text-xs text-rose-500 dark:text-rose-400 mt-1">{{ formErrors.title }}</p>
                </div>
                <div>
                    <label for="tplDesc" class="block text-xs font-medium text-secondary mb-1">
                        {{ t("welding.workflow_templates.field_description") }}
                    </label>
                    <textarea id="tplDesc" v-model="form.description" rows="3" class="w-full rounded border border-line bg-surface p-2 text-sm"></textarea>
                </div>
                <div>
                    <label for="tplScope" class="block text-xs font-medium text-secondary mb-1">
                        {{ t("welding.workflow_templates.field_applicable_to") }}
                    </label>
                    <input
                        id="tplScope"
                        v-model="form.applicableTo"
                        type="text"
                        class="w-full rounded border border-line bg-surface p-2 text-sm"
                        :placeholder="t('welding.workflow_templates.field_applicable_to_placeholder')"
                    />
                </div>
            </div>
            <template #footer>
                <AppButton variant="ghost" v-on:click="createOpen = false">{{ t("welding.runner.cancel") }}</AppButton>
                <AppButton variant="primary" :loading="requestLoading" :disabled="requestLoading" v-on:click="submitCreate">
                    {{ t("welding.workflow_templates.create_and_open") }}
                </AppButton>
            </template>
        </AppModal>

        <AppModal
            :show="pendingDelete !== null"
            :title="t('welding.workflow_templates.delete')"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-secondary">{{ t("welding.workflow_templates.confirm_delete") }}</p>
            <template #footer>
                <AppButton variant="ghost" v-on:click="pendingDelete = null">{{ t("welding.runner.cancel") }}</AppButton>
                <AppButton variant="danger" :loading="requestLoading" :disabled="requestLoading" v-on:click="doDelete">
                    {{ t("welding.workflow_templates.confirm_delete_button") }}
                </AppButton>
            </template>
        </AppModal>
    </div>
</template>
