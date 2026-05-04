import { ref, reactive, computed, watch, nextTick } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

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

    function buildTree(terms) {
        const byId = new Map(
            terms.map((term) => [term.id, { ...term, children: [] }]),
        );
        const roots = [];
        for (const node of byId.values()) {
            if (node.parentId && byId.has(node.parentId))
                byId.get(node.parentId).children.push(node);
            else roots.push(node);
        }
        const sortRecursive = (nodes) => {
            nodes.sort((a, b) => a.position - b.position || a.id - b.id);
            nodes.forEach((n) => sortRecursive(n.children));
        };
        sortRecursive(roots);
        return roots;
    }

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

    function flattenTreeForReorder(nodes, parentId = null) {
        const entries = [];
        nodes.forEach((node, index) => {
            entries.push({ id: node.id, parentId, position: index });
            if (node.children?.length)
                entries.push(...flattenTreeForReorder(node.children, node.id));
        });
        return entries;
    }

    async function persistTreeOrder() {
        if (!selected.value) return;
        const entries = flattenTreeForReorder(tree.value);
        try {
            const response = await fetch(
                buildPath(termReorderPath, { id: selected.value.id }),
                {
                    method: HttpMethod.Post,
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ entries }),
                },
            );
            const data = await response.json();
            if (!data.success)
                toast.error(data.error ?? t("shared.common.error"));
            else replaceTaxonomy(data.taxonomy);
        } catch {
            toast.error(t("shared.common.error"));
        }
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

    function collectDescendantIds(node) {
        const ids = new Set([node.id]);
        for (const child of node.children ?? [])
            collectDescendantIds(child).forEach((id) => ids.add(id));
        return ids;
    }

    function findNodeInTree(nodes, id) {
        for (const n of nodes) {
            if (n.id === id) return n;
            const found = findNodeInTree(n.children ?? [], id);
            if (found) return found;
        }
        return null;
    }

    const flatTermsForParentSelect = computed(() => {
        if (!selected.value?.hierarchical) return [];
        const list = [];
        const walk = (nodes, depth) =>
            nodes.forEach((n) => {
                list.push({
                    id: n.id,
                    label: `${"— ".repeat(depth)}${termName(n, activeLocale.value)}`,
                    descendants: collectDescendantIds(n),
                });
                if (n.children) walk(n.children, depth + 1);
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
