import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Inline reassignment of a categorization rule's category — the user
 * fixes a mis-learned mapping straight from the table without going
 * through a modal. Refuses no-op (newCategoryId equal to current).
 */
export function useCategorizationRuleUpdate(updatePath, onUpdated) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    async function setCategory(rule, newCategoryId) {
        if (!newCategoryId || newCategoryId === rule.categoryId) return;
        const payload = await request(buildPath(updatePath, { id: rule.id }), { categoryId: newCategoryId });
        if (!payload || payload.success === false) return;
        toast.success(t("personal_finance.categorization.updated"));
        onUpdated?.(payload.rule);
    }

    return { loading, setCategory };
}
