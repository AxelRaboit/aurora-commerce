import { describe, expect, it } from "vitest";
import {
    diffBlocks,
    applyMerge,
    countUnresolved,
    countConflicts,
    summarize,
    MergeKind,
} from "@/shared/utils/mergeBlocks.js";

function makeBlock(id, text) {
    return { id, type: "paragraph", data: { text } };
}

describe("diffBlocks", () => {
    it("returns empty array when all inputs are empty", () => {
        expect(diffBlocks([], [], [])).toEqual([]);
    });

    it("marks identical blocks as unchanged", () => {
        const block = makeBlock("a1", "hello");
        const entries = diffBlocks([block], [block], [block]);
        expect(entries).toHaveLength(1);
        expect(entries[0].kind).toBe(MergeKind.Unchanged);
        expect(entries[0].resolution).toBe("local");
    });

    it("detects local modification", () => {
        const base = makeBlock("a1", "hello");
        const local = makeBlock("a1", "hello edited");
        const remote = makeBlock("a1", "hello");
        const entries = diffBlocks([base], [local], [remote]);
        expect(entries[0].kind).toBe(MergeKind.LocalModified);
        expect(entries[0].resolution).toBe("local");
    });

    it("detects remote modification", () => {
        const base = makeBlock("a1", "hello");
        const local = makeBlock("a1", "hello");
        const remote = makeBlock("a1", "hello remote");
        const entries = diffBlocks([base], [local], [remote]);
        expect(entries[0].kind).toBe(MergeKind.RemoteModified);
        expect(entries[0].resolution).toBe("remote");
    });

    it("detects conflict when both sides modify the same block differently", () => {
        const base = makeBlock("a1", "hello");
        const local = makeBlock("a1", "hello A");
        const remote = makeBlock("a1", "hello B");
        const entries = diffBlocks([base], [local], [remote]);
        expect(entries[0].kind).toBe(MergeKind.Conflict);
        expect(entries[0].resolution).toBeNull();
    });

    it("treats identical local+remote modifications as unchanged (no conflict)", () => {
        const base = makeBlock("a1", "hello");
        const modified = makeBlock("a1", "hello same edit");
        const entries = diffBlocks([base], [modified], [modified]);
        expect(entries[0].kind).toBe(MergeKind.Unchanged);
    });

    it("detects local addition", () => {
        const newBlock = makeBlock("new", "new content");
        const entries = diffBlocks([], [newBlock], []);
        expect(entries[0].kind).toBe(MergeKind.LocalAdded);
        expect(entries[0].resolution).toBe("local");
    });

    it("detects remote addition", () => {
        const newBlock = makeBlock("new", "remote content");
        const entries = diffBlocks([], [], [newBlock]);
        expect(entries[0].kind).toBe(MergeKind.RemoteAdded);
        expect(entries[0].resolution).toBe("remote");
    });

    it("treats identical local+remote addition as unchanged", () => {
        const newBlock = makeBlock("new", "same content");
        const entries = diffBlocks([], [newBlock], [newBlock]);
        expect(entries[0].kind).toBe(MergeKind.Unchanged);
    });

    it("detects conflict when both sides add same id with different content", () => {
        const localBlock = makeBlock("x", "local version");
        const remoteBlock = makeBlock("x", "remote version");
        const entries = diffBlocks([], [localBlock], [remoteBlock]);
        expect(entries[0].kind).toBe(MergeKind.Conflict);
    });

    it("detects local removal (remote unchanged)", () => {
        const base = makeBlock("a1", "hello");
        const entries = diffBlocks([base], [], [base]);
        expect(entries[0].kind).toBe(MergeKind.LocalRemoved);
        expect(entries[0].resolution).toBe("local");
    });

    it("detects remote removal (local unchanged)", () => {
        const base = makeBlock("a1", "hello");
        const entries = diffBlocks([base], [base], []);
        expect(entries[0].kind).toBe(MergeKind.RemoteRemoved);
        expect(entries[0].resolution).toBe("remote");
    });

    it("detects conflict when local removes and remote modifies", () => {
        const base = makeBlock("a1", "hello");
        const remoteModified = makeBlock("a1", "hello changed");
        const entries = diffBlocks([base], [], [remoteModified]);
        expect(entries[0].kind).toBe(MergeKind.Conflict);
        expect(entries[0].resolution).toBeNull();
    });

    it("detects conflict when local modifies and remote removes", () => {
        const base = makeBlock("a1", "hello");
        const localModified = makeBlock("a1", "hello changed");
        const entries = diffBlocks([base], [localModified], []);
        expect(entries[0].kind).toBe(MergeKind.Conflict);
        expect(entries[0].resolution).toBeNull();
    });

    it("omits entries for blocks removed on both sides", () => {
        const base = makeBlock("a1", "hello");
        const entries = diffBlocks([base], [], []);
        expect(entries).toHaveLength(0);
    });

    it("handles a mix of unchanged, modified, added and conflicting blocks", () => {
        const unchanged = makeBlock("u", "stable");
        const base = makeBlock("m", "original");
        const localMod = makeBlock("m", "modified by me");
        const remoteMod = makeBlock("m", "modified remotely");
        const localNew = makeBlock("ln", "added by me");
        const remoteNew = makeBlock("rn", "added remotely");

        const entries = diffBlocks(
            [unchanged, base],
            [unchanged, localMod, localNew],
            [unchanged, remoteMod, remoteNew],
        );

        const byId = Object.fromEntries(entries.map((e) => [e.id, e]));
        expect(byId.u.kind).toBe(MergeKind.Unchanged);
        expect(byId.m.kind).toBe(MergeKind.Conflict);
        expect(byId.ln.kind).toBe(MergeKind.LocalAdded);
        expect(byId.rn.kind).toBe(MergeKind.RemoteAdded);
    });

    it("ignores blocks without an id", () => {
        const noId = { type: "paragraph", data: { text: "oops" } };
        const entries = diffBlocks([noId], [noId], [noId]);
        expect(entries).toEqual([]);
    });

    it("preserves local order then adds remote-only blocks", () => {
        const a = makeBlock("a", "first");
        const b = makeBlock("b", "second");
        const c = makeBlock("c", "third");
        const entries = diffBlocks([], [a, b], [c]);
        expect(entries.map((e) => e.id)).toEqual(["a", "b", "c"]);
    });
});

describe("applyMerge", () => {
    it("returns empty array when no entries", () => {
        expect(applyMerge([])).toEqual([]);
    });

    it("keeps local blocks for entries resolved as local", () => {
        const block = makeBlock("a", "mine");
        const result = applyMerge([
            {
                id: "a",
                kind: MergeKind.LocalAdded,
                base: null,
                local: block,
                remote: null,
                resolution: "local",
            },
        ]);
        expect(result).toEqual([block]);
    });

    it("keeps remote blocks for entries resolved as remote", () => {
        const block = makeBlock("a", "theirs");
        const result = applyMerge([
            {
                id: "a",
                kind: MergeKind.RemoteAdded,
                base: null,
                local: null,
                remote: block,
                resolution: "remote",
            },
        ]);
        expect(result).toEqual([block]);
    });

    it("skips entries resolved as a side with a null block (removals)", () => {
        const base = makeBlock("a", "old");
        const result = applyMerge([
            {
                id: "a",
                kind: MergeKind.LocalRemoved,
                base,
                local: null,
                remote: base,
                resolution: "local",
            },
        ]);
        expect(result).toEqual([]);
    });

    it("skips unresolved entries (resolution null)", () => {
        const block = makeBlock("a", "mine");
        const result = applyMerge([
            {
                id: "a",
                kind: MergeKind.Conflict,
                base: null,
                local: block,
                remote: block,
                resolution: null,
            },
        ]);
        expect(result).toEqual([]);
    });

    it("preserves entry order", () => {
        const a = makeBlock("a", "1");
        const b = makeBlock("b", "2");
        const c = makeBlock("c", "3");
        const result = applyMerge([
            {
                id: "a",
                kind: MergeKind.Unchanged,
                base: a,
                local: a,
                remote: a,
                resolution: "local",
            },
            {
                id: "b",
                kind: MergeKind.Unchanged,
                base: b,
                local: b,
                remote: b,
                resolution: "local",
            },
            {
                id: "c",
                kind: MergeKind.Unchanged,
                base: c,
                local: c,
                remote: c,
                resolution: "local",
            },
        ]);
        expect(result.map((x) => x.id)).toEqual(["a", "b", "c"]);
    });
});

describe("countUnresolved / countConflicts / summarize", () => {
    const block = makeBlock("x", "content");
    const entries = [
        {
            id: "1",
            kind: MergeKind.Unchanged,
            base: block,
            local: block,
            remote: block,
            resolution: "local",
        },
        {
            id: "2",
            kind: MergeKind.LocalModified,
            base: block,
            local: block,
            remote: block,
            resolution: "local",
        },
        {
            id: "3",
            kind: MergeKind.Conflict,
            base: block,
            local: block,
            remote: block,
            resolution: null,
        },
        {
            id: "4",
            kind: MergeKind.Conflict,
            base: block,
            local: block,
            remote: block,
            resolution: "local",
        },
    ];

    it("counts conflicts regardless of resolution", () => {
        expect(countConflicts(entries)).toBe(2);
    });

    it("counts only unresolved (null resolution) conflicts", () => {
        expect(countUnresolved(entries)).toBe(1);
    });

    it("summarize groups unchanged / auto-resolved / conflicts", () => {
        expect(summarize(entries)).toEqual({
            unchanged: 1,
            autoResolved: 1,
            conflicts: 2,
        });
    });

    it("handles empty/nullish input safely", () => {
        expect(countConflicts()).toBe(0);
        expect(countUnresolved(null)).toBe(0);
        expect(summarize(undefined)).toEqual({
            unchanged: 0,
            autoResolved: 0,
            conflicts: 0,
        });
    });
});
