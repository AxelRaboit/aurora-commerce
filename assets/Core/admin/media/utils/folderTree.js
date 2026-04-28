export function buildFolderTree(list) {
    const byId = new Map(list.map((f) => [f.id, { ...f, children: [] }]));
    const roots = [];
    for (const node of byId.values()) {
        if (node.parentId && byId.has(node.parentId)) {
            byId.get(node.parentId).children.push(node);
        } else {
            roots.push(node);
        }
    }
    const sort = (nodes) => {
        nodes.sort((a, b) => a.name.localeCompare(b.name));
        nodes.forEach((n) => sort(n.children));
    };
    sort(roots);
    return roots;
}

export function flattenFolders(nodes, depth = 0, skipDescendantsOf = null) {
    const result = [];
    for (const node of nodes) {
        result.push({
            ...node,
            depth,
            childCount: node.children.length,
            mediaCount: node.mediaCount ?? 0,
        });
        const collapsed = skipDescendantsOf?.has(node.id) ?? false;
        if (node.children.length && !collapsed) {
            result.push(
                ...flattenFolders(node.children, depth + 1, skipDescendantsOf),
            );
        }
    }
    return result;
}
