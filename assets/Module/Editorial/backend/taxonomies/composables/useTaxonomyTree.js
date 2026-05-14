import { ref, reactive, computed, watch, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import {
    buildTree,
    flattenTreeForReorder,
    collectDescendantIds,
    findNodeInTree,
} from "@/shared/composables/tree/useHierarchicalTree.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useTaxonomyTree(
    selected,
    flatTerms,
    termReorderPath,
    locales,
    activeLocale,
    replaceTaxonomy,
    termName,
) {
    const { t } = useI18n();
    const { request } = useRequest();

    const tree = ref([]);

    watch(
        () => selected.value?.id,
        () => {
            tree.value = buildTree(flatTerms.value);
        },
        { immediate: true },
    );
    watch(
        flatTerms,
        (terms) => {
            tree.value = buildTree(terms);
        },
        { deep: true },
    );

    const dragging = ref(false);

    async function persistTreeOrder() {
        if (!selected.value) return;
        const entries = flattenTreeForReorder(tree.value);
        const data = await request(
            buildPath(termReorderPath, { id: selected.value.id }),
            { entries },
        );
        if (!data) return;
        if (!data.success) toast.error(data.error ?? t("shared.common.error"));
        else replaceTaxonomy(data.taxonomy);
    }

    function onDragEnd() {
        dragging.value = false;
        nextTick(() => persistTreeOrder());
    }

    const collapsed = reactive(new Set());
    function toggleCollapsed(id) {
        if (collapsed.has(id)) collapsed.delete(id);
        else collapsed.add(id);
    }

    const flatTermsForParentSelect = computed(() => {
        if (!selected.value?.hierarchical) return [];
        const list = [];
        const walk = (nodes, depth) =>
            nodes.forEach((node) => {
                list.push({
                    id: node.id,
                    label: `${"— ".repeat(depth)}${termName(node, activeLocale.value)}`,
                    descendants: collectDescendantIds(node),
                });
                if (node.children) walk(node.children, depth + 1);
            });
        walk(tree.value, 0);
        return list;
    });

    return {
        tree,
        dragging,
        collapsed,
        toggleCollapsed,
        onDragEnd,
        flatTermsForParentSelect,
        collectDescendantIds,
        findNodeInTree,
    };
}
