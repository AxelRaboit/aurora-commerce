export {
    buildFolderTree,
    getFolderAncestors,
    getFolderDescendantIds,
} from "@shared/utils/tree/folderTree.js";

import { flattenFolders as flattenFoldersGeneric } from "@shared/utils/tree/folderTree.js";

/**
 * Media-specific flatten: adds `mediaCount` to each node.
 */
export function flattenFolders(nodes, depth = 0, skipDescendantsOf = null) {
    return flattenFoldersGeneric(nodes, depth, skipDescendantsOf).map(
        (node) => ({
            ...node,
            mediaCount: node.mediaCount ?? 0,
        }),
    );
}
