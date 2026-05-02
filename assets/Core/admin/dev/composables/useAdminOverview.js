import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

/**
 * Lazy-loaded stats payload for the dev dashboard Overview tab.
 * Mirrors the shape of useAdminUsers / useAdminParameters / useAdminAccessRequests
 * so the parent component can call `.load()` uniformly when switching tabs.
 */
export function useAdminOverview(overviewPath, initialStats) {
    const { t } = useI18n();
    const stats = ref(initialStats ?? {});
    const loading = ref(false);

    async function load() {
        loading.value = true;
        try {
            const response = await fetch(overviewPath, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (!response.ok) throw new Error();
            const data = await response.json();
            stats.value = data.stats ?? {};
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { stats, loading, load };
}
