<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { ClipboardCheck, Plus } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { useWorkflowStatus } from "@welding/backend/composables/useWeldingStatus.js";

const { t } = useI18n();

const items = ref([]);
const page = ref(1);
const totalPages = ref(1);
const total = ref(0);
const query = ref("");
const statusFilter = ref("");
const listLoading = ref(false);

const { ORDER: STATUS_ORDER, COLOR: STATUS_COLOR } = useWorkflowStatus();

async function fetchList() {
    listLoading.value = true;
    try {
        const params = new URLSearchParams({ page: String(page.value), limit: "20" });
        if (query.value) params.set("search", query.value);
        if (statusFilter.value) params.set("status", statusFilter.value);

        const res = await fetch(`/backend/welding/workflows/list?${params}`, {
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        });
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;
        items.value = data.items;
        page.value = data.page;
        totalPages.value = data.totalPages;
        total.value = data.total;
    } finally {
        listLoading.value = false;
    }
}

onMounted(fetchList);

const debouncedRefetch = useDebounce(() => {
    page.value = 1;
    fetchList();
}, 300);

watch(query, debouncedRefetch);
watch(statusFilter, () => {
    page.value = 1;
    fetchList();
});

function goToPage(newPage) {
    page.value = newPage;
    fetchList();
}

const startOpen = ref(false);
const templateOptions = ref([]);
const employeeOptions = ref([]);
const startForm = ref({ templateId: "", assigneeId: "" });
const optionsLoading = ref(false);

const publishedTemplates = computed(() =>
    templateOptions.value.filter((opt) => opt.status === "published"),
);

const { loading: createLoading, request } = useRequest();

async function fetchJson(url) {
    const res = await fetch(url, { headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" } });
    if (!res.ok) return null;
    return res.json();
}

async function openStart() {
    startForm.value = { templateId: "", assigneeId: "" };
    startOpen.value = true;
    if (templateOptions.value.length === 0 && employeeOptions.value.length === 0) {
        optionsLoading.value = true;
        try {
            const [tpl, emp] = await Promise.all([
                fetchJson("/backend/welding/workflow-templates/options"),
                fetchJson("/backend/welding/options/employees"),
            ]);
            if (tpl?.success) templateOptions.value = tpl.items;
            if (emp?.success) employeeOptions.value = emp.items;
        } finally {
            optionsLoading.value = false;
        }
    }
}

async function submitStart() {
    if (!startForm.value.templateId) {
        toast.error(t("welding.workflows.errors.template_required"));
        return;
    }
    const payload = {
        templateId: Number(startForm.value.templateId),
        assigneeId: startForm.value.assigneeId ? Number(startForm.value.assigneeId) : null,
    };
    const data = await request("/backend/welding/workflows", payload);
    if (!data?.success) return;

    toast.success(t("welding.workflows.created"));
    window.location.href = `/backend/welding/workflows/${data.workflow.id}/runner`;
}
</script>

<template>
    <div class="p-4 sm:p-6 space-y-6">
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
            <div class="flex flex-col sm:flex-row gap-2">
                <AppSearchInput
                    v-model="query"
                    :placeholder="t('welding.workflows.search_placeholder')"
                    class="flex-1"
                />
                <select
                    v-model="statusFilter"
                    class="rounded border border-line bg-surface px-3 py-2 text-sm"
                    :aria-label="t('welding.workflows.filter_status')"
                >
                    <option value="">{{ t("welding.workflows.filter_all") }}</option>
                    <option v-for="status in STATUS_ORDER" :key="status" :value="status">
                        {{ t("welding.workflows.status_" + status) }}
                    </option>
                </select>
            </div>
            <template #actions>
                <AppButton variant="primary" v-on:click="openStart">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("welding.workflows.new") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <div v-if="listLoading && items.length === 0" class="rounded-xl border border-line bg-surface p-6 text-sm text-secondary text-center">
            {{ t("welding.workflows.loading") }}
        </div>
        <div v-else-if="items.length === 0 && total === 0 && !query && !statusFilter" class="rounded-xl border border-line bg-surface p-6 text-sm text-secondary text-center">
            {{ t("welding.workflows.empty") }}
        </div>
        <div v-else-if="items.length === 0" class="rounded-xl border border-line bg-surface p-6 text-sm text-secondary text-center">
            {{ t("welding.workflows.search_no_match") }}
        </div>
        <ul v-else class="space-y-2">
            <li v-for="workflow in items" :key="workflow.id">
                <a
                    :href="`/backend/welding/workflows/${workflow.id}/runner`"
                    class="block rounded-lg border border-line bg-surface p-3 hover:border-accent-300 dark:hover:border-accent-700 transition-colors"
                >
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <span class="font-mono text-xs text-secondary">{{ workflow.reference }}</span>
                        <span :class="['text-xs px-2 py-0.5 rounded-full', STATUS_COLOR[workflow.status]]">
                            {{ t("welding.workflows.status_" + workflow.status) }}
                        </span>
                    </div>
                    <div class="text-sm font-medium text-primary truncate mt-1">
                        {{ workflow.templateTitle || "—" }}
                        <span class="text-xs text-muted">v{{ workflow.templateVersion }}</span>
                    </div>
                    <div class="text-xs text-secondary truncate">
                        {{ workflow.assigneeName || t("welding.workflows.no_assignee") }}
                    </div>
                </a>
            </li>
        </ul>

        <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />

        <AppModal :show="startOpen" :title="t('welding.workflows.new')" v-on:close="startOpen = false">
            <div v-if="optionsLoading" class="text-sm text-secondary">{{ t("welding.workflows.loading_options") }}</div>
            <div v-else class="space-y-3">
                <div>
                    <label for="startTemplate" class="block text-xs font-medium text-secondary mb-1">
                        {{ t("welding.workflows.field_template") }} *
                    </label>
                    <select id="startTemplate" v-model="startForm.templateId" class="w-full rounded border border-line bg-surface p-2 text-sm">
                        <option value="">—</option>
                        <option v-for="opt in publishedTemplates" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <p v-if="publishedTemplates.length === 0" class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        {{ t("welding.workflows.no_published_template") }}
                    </p>
                </div>
                <div>
                    <label for="startAssignee" class="block text-xs font-medium text-secondary mb-1">
                        {{ t("welding.workflows.field_assignee") }}
                    </label>
                    <select id="startAssignee" v-model="startForm.assigneeId" class="w-full rounded border border-line bg-surface p-2 text-sm">
                        <option value="">— {{ t("welding.workflows.no_assignee_option") }}</option>
                        <option v-for="opt in employeeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>
            </div>
            <template #footer>
                <AppButton variant="ghost" v-on:click="startOpen = false">{{ t("welding.runner.cancel") }}</AppButton>
                <AppButton
                    variant="primary"
                    :loading="createLoading"
                    :disabled="optionsLoading || createLoading || publishedTemplates.length === 0"
                    v-on:click="submitStart"
                >
                    {{ t("welding.workflows.create_and_open") }}
                </AppButton>
            </template>
        </AppModal>
    </div>
</template>
