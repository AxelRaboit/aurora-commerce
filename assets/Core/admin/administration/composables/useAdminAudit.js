import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

/**
 * Audit log fetcher with module filtering. Exposes `module` (ref) so the
 * dropdown is two-way bound; setting it triggers an XHR reload.
 */
export function useAdminAudit(auditPath, initialAudit) {
    const { t } = useI18n();
    const data = ref(initialAudit ?? {});
    const loading = ref(false);
    const module = ref(initialAudit?.module ?? "");

    async function load() {
        loading.value = true;
        try {
            const url = new URL(auditPath, window.location.origin);
            if (module.value) url.searchParams.set("module", module.value);
            const response = await fetch(url, {
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

    function setModule(value) {
        module.value = value;
        load();
    }

    return { data, loading, load, module, setModule };
}
