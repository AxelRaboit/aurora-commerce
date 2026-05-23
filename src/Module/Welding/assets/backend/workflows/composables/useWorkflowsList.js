import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Workflows index page — paginated list (search + status filter) and the
 * "Start workflow" modal (template + assignee selection, redirects to the
 * runner on success).
 */
export function useWorkflowsList() {
    const { t } = useI18n();
    const statusFilter = ref("");

    const {
        items,
        loading: listLoading,
        page,
        totalPages,
        total,
        search,
        onSearch,
        goToPage,
        reload,
    } = useListPage("/backend/welding/workflows/list", {
        extraParams: () =>
            statusFilter.value ? { status: statusFilter.value } : {},
    });

    // Refetch when the filter changes (search already handled by useListPage).
    watch(statusFilter, () => reload());

    // ── Start modal ───────────────────────────────────────────────────────
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
        const res = await fetch(url, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });
        if (!res.ok) return null;
        return res.json();
    }

    async function openStart() {
        startForm.value = { templateId: "", assigneeId: "" };
        startOpen.value = true;
        if (
            templateOptions.value.length === 0 &&
            employeeOptions.value.length === 0
        ) {
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
            assigneeId: startForm.value.assigneeId
                ? Number(startForm.value.assigneeId)
                : null,
        };
        const data = await request("/backend/welding/workflows", payload);
        if (!data?.success) return;

        toast.success(t("welding.workflows.created"));
        window.location.href = `/backend/welding/workflows/${data.workflow.id}/runner`;
    }

    return {
        // List
        items,
        listLoading,
        page,
        totalPages,
        total,
        search,
        onSearch,
        goToPage,
        statusFilter,
        // Start modal
        startOpen,
        templateOptions,
        employeeOptions,
        publishedTemplates,
        startForm,
        optionsLoading,
        createLoading,
        openStart,
        submitStart,
    };
}
