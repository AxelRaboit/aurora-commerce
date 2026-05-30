import { describe, expect, it } from "vitest";
import { buildFolderTree, flattenFolders } from "./folderTree.js";

const folders = [
    { id: 3, parentId: 1, name: "beta", mediaCount: 2 },
    { id: 1, parentId: null, name: "alpha" },
    { id: 2, parentId: null, name: "gamma" },
    { id: 4, parentId: 1, name: "alpha-child" },
];

describe("buildFolderTree", () => {
    it("returns roots sorted alphabetically", () => {
        const tree = buildFolderTree(folders);
        expect(tree.map((n) => n.name)).toEqual(["alpha", "gamma"]);
    });

    it("nests children under their parent and sorts them", () => {
        const tree = buildFolderTree(folders);
        const alpha = tree.find((n) => n.name === "alpha");
        expect(alpha.children.map((c) => c.name)).toEqual([
            "alpha-child",
            "beta",
        ]);
    });

    it("treats unknown parents as roots", () => {
        const orphan = [{ id: 9, parentId: 99, name: "z" }];
        expect(buildFolderTree(orphan)).toHaveLength(1);
    });
});

describe("flattenFolders", () => {
    it("flattens depth-first with depth and counts", () => {
        const tree = buildFolderTree(folders);
        const flat = flattenFolders(tree);
        expect(flat.map((n) => [n.name, n.depth])).toEqual([
            ["alpha", 0],
            ["alpha-child", 1],
            ["beta", 1],
            ["gamma", 0],
        ]);
    });

    it("propagates mediaCount and childCount", () => {
        const tree = buildFolderTree(folders);
        const flat = flattenFolders(tree);
        const beta = flat.find((n) => n.name === "beta");
        expect(beta.mediaCount).toBe(2);
        const alpha = flat.find((n) => n.name === "alpha");
        expect(alpha.childCount).toBe(2);
    });

    it("hides descendants of folders listed in skipDescendantsOf", () => {
        const tree = buildFolderTree(folders);
        const skip = new Set([1]);
        const flat = flattenFolders(tree, 0, skip);
        expect(flat.map((n) => n.name)).toEqual(["alpha", "gamma"]);
    });
});
