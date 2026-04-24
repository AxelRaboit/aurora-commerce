export const MergeKind = Object.freeze({
    Unchanged: "unchanged",
    LocalModified: "local-modified",
    RemoteModified: "remote-modified",
    LocalAdded: "local-added",
    RemoteAdded: "remote-added",
    LocalRemoved: "local-removed",
    RemoteRemoved: "remote-removed",
    Conflict: "conflict",
});

function blocksEqual(a, b) {
    if (a === b) return true;
    if (!a || !b) return false;
    return JSON.stringify(a) === JSON.stringify(b);
}

function toMap(blocks) {
    const map = new Map();
    (blocks ?? []).forEach((block, index) => {
        if (block?.id) map.set(block.id, { block, index });
    });
    return map;
}

function orderedUnionIds(localBlocks, remoteBlocks, baseBlocks) {
    const seen = new Set();
    const ids = [];
    for (const source of [localBlocks, remoteBlocks, baseBlocks]) {
        for (const block of source ?? []) {
            if (block?.id && !seen.has(block.id)) {
                ids.push(block.id);
                seen.add(block.id);
            }
        }
    }
    return ids;
}

/**
 * Classify a single block across base / local / remote into a merge kind and
 * a default resolution. Returns null when the block is absent from all three
 * sides (should be filtered out).
 */
export function classifyEntry(base, local, remote) {
    if (base && local && remote) {
        const localSame = blocksEqual(base, local);
        const remoteSame = blocksEqual(base, remote);
        if (localSame && remoteSame)
            return { kind: MergeKind.Unchanged, resolution: "local" };
        if (!localSame && remoteSame)
            return { kind: MergeKind.LocalModified, resolution: "local" };
        if (localSame && !remoteSame)
            return { kind: MergeKind.RemoteModified, resolution: "remote" };
        if (blocksEqual(local, remote))
            return { kind: MergeKind.Unchanged, resolution: "local" };
        return { kind: MergeKind.Conflict, resolution: null };
    }
    if (!base && local && remote) {
        return blocksEqual(local, remote)
            ? { kind: MergeKind.Unchanged, resolution: "local" }
            : { kind: MergeKind.Conflict, resolution: null };
    }
    if (!base && local && !remote)
        return { kind: MergeKind.LocalAdded, resolution: "local" };
    if (!base && !local && remote)
        return { kind: MergeKind.RemoteAdded, resolution: "remote" };
    if (base && !local && remote) {
        return blocksEqual(base, remote)
            ? { kind: MergeKind.LocalRemoved, resolution: "local" }
            : { kind: MergeKind.Conflict, resolution: null };
    }
    if (base && local && !remote) {
        return blocksEqual(base, local)
            ? { kind: MergeKind.RemoteRemoved, resolution: "remote" }
            : { kind: MergeKind.Conflict, resolution: null };
    }
    return null;
}

export function diffBlocks(baseBlocks, localBlocks, remoteBlocks) {
    const baseMap = toMap(baseBlocks);
    const localMap = toMap(localBlocks);
    const remoteMap = toMap(remoteBlocks);

    const entries = [];
    for (const id of orderedUnionIds(localBlocks, remoteBlocks, baseBlocks)) {
        const base = baseMap.get(id)?.block ?? null;
        const local = localMap.get(id)?.block ?? null;
        const remote = remoteMap.get(id)?.block ?? null;

        const classification = classifyEntry(base, local, remote);
        if (!classification) continue;

        entries.push({ id, ...classification, base, local, remote });
    }

    return entries;
}

export function applyMerge(entries) {
    const result = [];
    for (const entry of entries) {
        if (entry.resolution === "local" && entry.local) {
            result.push(entry.local);
        } else if (entry.resolution === "remote" && entry.remote) {
            result.push(entry.remote);
        }
    }
    return result;
}

export function countUnresolved(entries) {
    return (entries ?? []).filter(
        (e) => e.kind === MergeKind.Conflict && e.resolution === null,
    ).length;
}

export function countConflicts(entries) {
    return (entries ?? []).filter((e) => e.kind === MergeKind.Conflict).length;
}

export function summarize(entries) {
    const stats = { unchanged: 0, autoResolved: 0, conflicts: 0 };
    for (const entry of entries ?? []) {
        if (entry.kind === MergeKind.Unchanged) stats.unchanged++;
        else if (entry.kind === MergeKind.Conflict) stats.conflicts++;
        else stats.autoResolved++;
    }
    return stats;
}
