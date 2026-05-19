/**
 * Vault-specific tree helpers built on top of the shared folderTree utilities.
 * The vault sorts folders by position then name (vs Media's name-only sort).
 */
import {
    buildFolderTree as buildFolderTreeGeneric,
    flattenFolders,
    getFolderAncestors,
    getFolderDescendantIds,
} from "@shared/utils/tree/folderTree.js";

export {
    flattenFolders,
    getFolderAncestors as getAncestors,
    getFolderDescendantIds as getDescendantIds,
};

const vaultFolderSort = (a, b) =>
    a.position - b.position || a.name.localeCompare(b.name);

export function buildTree(folders) {
    return buildFolderTreeGeneric(folders, vaultFolderSort);
}
