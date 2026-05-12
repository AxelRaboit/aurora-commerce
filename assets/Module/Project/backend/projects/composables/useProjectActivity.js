import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useProjectActivity(activityPath, activeProject) {
    const { t } = useI18n();
    const entries = ref([]);
    const loading = ref(false);
    const showActivity = ref(false);

    async function load() {
        if (!activeProject.value) {
            entries.value = [];
            return;
        }
        loading.value = true;
        try {
            const url = buildPath(activityPath, { id: activeProject.value.id });
            const response = await fetch(url, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            const data = await response.json();
            if (data.success) {
                entries.value = data.entries ?? [];
            }
        } finally {
            loading.value = false;
        }
    }

    // Reload whenever the active project changes (open/close/reload).
    watch(
        () => activeProject.value?.id,
        () => load(),
        { immediate: true },
    );

    function toggleActivity() {
        showActivity.value = !showActivity.value;
        if (showActivity.value) load();
    }

    function formatRelativeDate(iso) {
        const date = new Date(iso);
        const diffSeconds = Math.round((Date.now() - date.getTime()) / 1000);
        if (diffSeconds < 60) return t("backend.projects.activity.justNow");
        if (diffSeconds < 3600)
            return t("backend.projects.activity.minutesAgo", {
                n: Math.floor(diffSeconds / 60),
            });
        if (diffSeconds < 86400)
            return t("backend.projects.activity.hoursAgo", {
                n: Math.floor(diffSeconds / 3600),
            });
        return date.toLocaleDateString();
    }

    return {
        entries,
        loading,
        showActivity,
        toggleActivity,
        formatRelativeDate,
        reload: load,
    };
}
