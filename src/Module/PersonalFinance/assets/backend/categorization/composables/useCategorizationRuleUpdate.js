import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

/**
 * Inline reassignment of a categorization rule's category — the user
 * fixes a mis-learned mapping straight from the table without going
 * through a modal. Refuses no-op (newCategoryId equal to current).
 */
export function useCategorizationRuleUpdate(updatePath, onUpdated) {
    const { t } = useI18n();
    const loading = ref(false);

    async function setCategory(rule, newCategoryId) {
        if (!newCategoryId || newCategoryId === rule.categoryId) return;
        if (loading.value) return;
        loading.value = true;
        try {
            const url = buildPath(updatePath, { id: rule.id });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { Accept: "application/json", "Content-Type": "application/json" },
                body: JSON.stringify({ categoryId: newCategoryId }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                toast.error(t("shared.common.error"));
                return;
            }
            toast.success(t("personal_finance.categorization.updated"));
            onUpdated?.(payload.rule);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { loading, setCategory };
}
