import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

/**
 * Lazy-loaded module/permission registry for the dev dashboard Permissions
 * tab. Read-only — there is no mutation API.
 */
export function useAdminPermissions(permissionsPath, initialPermissions) {
    const { t } = useI18n();
    const data = ref(initialPermissions ?? { modules: [] });
    const loading = ref(false);

    async function load() {
        loading.value = true;
        try {
            const response = await fetch(permissionsPath, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (!response.ok) throw new Error();
            data.value = await response.json();
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { data, loading, load };
}
