<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { ScrollText, Plus, Pencil, Send, Archive, Copy, Trash2 } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

const props = defineProps({
    workflowTemplates: { type: Array, default: () => [] },
});

const { t } = useI18n();
const items = ref([...props.workflowTemplates]);

const createOpen = ref(false);
const form = ref({ title: "", description: "", applicableTo: "" });
const formErrors = ref({});

const { request } = useRequest();

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
    } else if (data.errors) {
        formErrors.value = data.errors;
    }
}

async function publish(template) {
    const data = await request(`/backend/welding/workflow-templates/${template.id}/publish`, {});
    if (data?.success) {
        toast.success(t("welding.workflow_templates.published"));
        window.location.reload();
    }
}

async function archive(template) {
    const data = await request(`/backend/welding/workflow-templates/${template.id}/archive`, {});
    if (data?.success) {
        toast.success(t("welding.workflow_templates.archived"));
        window.location.reload();
    }
}

async function clone(template) {
    const data = await request(`/backend/welding/workflow-templates/${template.id}/clone`, {});
    if (data?.success) {
        toast.success(t("welding.workflow_templates.cloned"));
        window.location.href = `/backend/welding/workflow-templates/${data.workflowTemplate.id}/editor`;
    }
}

async function remove(template) {
    if (!confirm(t("welding.workflow_templates.confirm_delete"))) return;
    const data = await request(`/backend/welding/workflow-templates/${template.id}/delete`, {});
    if (data?.success) {
        toast.success(t("welding.workflow_templates.deleted"));
        items.value = items.value.filter((x) => x.id !== template.id);
    }
}

const STATUS_BADGE = {
    draft: "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300",
    published: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400",
    archived: "bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400",
};
</script>

<template>
    <div class="p-6 space-y-6">
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
                <Plus class="w-4 h-4" /> {{ t("welding.workflow_templates.new") }}
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
                    <AppButton variant="ghost" size="sm" :href="`/backend/welding/workflow-templates/${template.id}/editor`">
                        <Pencil class="w-3.5 h-3.5" /> {{ t("welding.workflow_templates.open") }}
                    </AppButton>
                    <AppButton v-if="template.status === 'draft'" variant="ghost" size="sm" v-on:click="publish(template)">
                        <Send class="w-3.5 h-3.5" /> {{ t("welding.workflow_templates.publish") }}
                    </AppButton>
                    <AppButton v-if="template.status === 'published'" variant="ghost" size="sm" v-on:click="clone(template)">
                        <Copy class="w-3.5 h-3.5" /> {{ t("welding.workflow_templates.clone") }}
                    </AppButton>
                    <AppButton v-if="template.status !== 'archived'" variant="ghost" size="sm" v-on:click="archive(template)">
                        <Archive class="w-3.5 h-3.5" /> {{ t("welding.workflow_templates.archive") }}
                    </AppButton>
                    <AppButton variant="ghost" size="sm" v-on:click="remove(template)">
                        <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                    </AppButton>
                </div>
            </li>
        </ul>

        <!-- Create modal -->
        <AppModal :show="createOpen" :title="t('welding.workflow_templates.new')" v-on:close="createOpen = false">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-secondary mb-1">{{ t("welding.workflow_templates.field_title") }} *</label>
                    <input v-model="form.title" type="text" class="w-full rounded border border-line bg-surface p-2 text-sm" />
                    <p v-if="formErrors.title" class="text-xs text-rose-500 mt-1">{{ formErrors.title }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-secondary mb-1">{{ t("welding.workflow_templates.field_description") }}</label>
                    <textarea v-model="form.description" rows="3" class="w-full rounded border border-line bg-surface p-2 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-secondary mb-1">{{ t("welding.workflow_templates.field_applicable_to") }}</label>
                    <input v-model="form.applicableTo" type="text" class="w-full rounded border border-line bg-surface p-2 text-sm" :placeholder="t('welding.workflow_templates.field_applicable_to_placeholder')" />
                </div>
            </div>
            <template #footer>
                <AppButton variant="ghost" v-on:click="createOpen = false">{{ t("welding.runner.cancel") }}</AppButton>
                <AppButton variant="primary" v-on:click="submitCreate">{{ t("welding.workflow_templates.create_and_open") }}</AppButton>
            </template>
        </AppModal>
    </div>
</template>
