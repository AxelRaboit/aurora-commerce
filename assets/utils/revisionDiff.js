export const RevisionDiffKind = Object.freeze({
    Unchanged: "unchanged",
    Added: "added",
    Removed: "removed",
    Modified: "modified",
});

function blocksEqual(a, b) {
    if (a === b) return true;
    if (!a || !b) return false;
    return JSON.stringify(a) === JSON.stringify(b);
}

function toMap(blocks) {
    const map = new Map();
    (blocks ?? []).forEach((block) => {
        if (block?.id) map.set(block.id, block);
    });
    return map;
}

/**
 * Two-way diff between the current blocks and a revision's blocks.
 * Returns ordered entries: current order first, then removed blocks.
 */
export function diffBlocksAgainstRevision(currentBlocks, revisionBlocks) {
    const currentMap = toMap(currentBlocks);
    const revisionMap = toMap(revisionBlocks);

    const entries = [];
    const seen = new Set();

    for (const block of currentBlocks ?? []) {
        if (!block?.id) continue;
        seen.add(block.id);
        const previous = revisionMap.get(block.id) ?? null;
        if (!previous) {
            entries.push({ id: block.id, kind: RevisionDiffKind.Added, current: block, revision: null });
        } else if (!blocksEqual(block, previous)) {
            entries.push({ id: block.id, kind: RevisionDiffKind.Modified, current: block, revision: previous });
        } else {
            entries.push({ id: block.id, kind: RevisionDiffKind.Unchanged, current: block, revision: previous });
        }
    }

    for (const block of revisionBlocks ?? []) {
        if (!block?.id || seen.has(block.id)) continue;
        if (currentMap.has(block.id)) continue;
        entries.push({ id: block.id, kind: RevisionDiffKind.Removed, current: null, revision: block });
    }

    return entries;
}

export function summarizeRevisionDiff(entries) {
    const stats = { unchanged: 0, added: 0, removed: 0, modified: 0 };
    for (const entry of entries ?? []) {
        if (entry.kind === RevisionDiffKind.Unchanged) stats.unchanged++;
        else if (entry.kind === RevisionDiffKind.Added) stats.added++;
        else if (entry.kind === RevisionDiffKind.Removed) stats.removed++;
        else if (entry.kind === RevisionDiffKind.Modified) stats.modified++;
    }
    return stats;
}
