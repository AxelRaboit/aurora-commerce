import { describe, it, expect } from "vitest";
import {
    diffBlocksAgainstRevision,
    summarizeRevisionDiff,
    RevisionDiffKind,
} from "./revisionDiff.js";

describe("diffBlocksAgainstRevision", () => {
    it("marks unchanged blocks", () => {
        const block = { id: "1", type: "text", content: "hello" };
        const result = diffBlocksAgainstRevision([block], [block]);
        expect(result).toHaveLength(1);
        expect(result[0].kind).toBe(RevisionDiffKind.Unchanged);
    });

    it("marks added blocks (present in current, absent in revision)", () => {
        const result = diffBlocksAgainstRevision(
            [{ id: "1", type: "text" }],
            [],
        );
        expect(result[0].kind).toBe(RevisionDiffKind.Added);
        expect(result[0].revision).toBeNull();
    });

    it("marks removed blocks (absent in current, present in revision)", () => {
        const result = diffBlocksAgainstRevision(
            [],
            [{ id: "1", type: "text" }],
        );
        expect(result[0].kind).toBe(RevisionDiffKind.Removed);
        expect(result[0].current).toBeNull();
    });

    it("marks modified blocks (same id, different content)", () => {
        const current = { id: "1", content: "new" };
        const revision = { id: "1", content: "old" };
        const result = diffBlocksAgainstRevision([current], [revision]);
        expect(result[0].kind).toBe(RevisionDiffKind.Modified);
    });

    it("handles null/undefined gracefully", () => {
        expect(diffBlocksAgainstRevision(null, null)).toEqual([]);
    });
});

describe("summarizeRevisionDiff", () => {
    it("returns correct counts for mixed entries", () => {
        const entries = [
            { kind: RevisionDiffKind.Unchanged },
            { kind: RevisionDiffKind.Added },
            { kind: RevisionDiffKind.Added },
            { kind: RevisionDiffKind.Removed },
            { kind: RevisionDiffKind.Modified },
        ];
        expect(summarizeRevisionDiff(entries)).toEqual({
            unchanged: 1,
            added: 2,
            removed: 1,
            modified: 1,
        });
    });
});
