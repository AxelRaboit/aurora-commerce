import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function useTaxonomyDelete(deletePath, taxonomies, selectedId) {
    const { t } = useI18n();
    const deletingTaxonomy = ref(null);

    async function confirmDeleteTaxonomy() {
        const taxonomy = deletingTaxonomy.value;
        if (!taxonomy) return;
        try {
            const response = await fetch(
                buildPath(deletePath, { id: taxonomy.id }),
                { method: HttpMethod.Post },
            );
            const data = await response.json();
            if (!data.success) {
                toast.error(data.error ?? t("shared.common.error"));
                return;
            }
            taxonomies.value = taxonomies.value.filter(
                (tx) => tx.id !== taxonomy.id,
            );
            if (selectedId.value === taxonomy.id)
                selectedId.value = taxonomies.value[0]?.id ?? null;
            toast.success(t("shared.common.deleted"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            deletingTaxonomy.value = null;
        }
    }

    return { deletingTaxonomy, confirmDeleteTaxonomy };
}
