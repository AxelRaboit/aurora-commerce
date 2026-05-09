/**
 * Tailwind class strings for the 6 deal stages, used as badge/pill backgrounds
 * across DealsApp (list view, kanban tabs) and DealDetailApp (header badge,
 * AppStagePicker). Keeps the colour palette in one place.
 */
const STAGE_BG = {
    lead: "bg-slate-500/15 text-slate-400",
    qualified: "bg-blue-500/15 text-blue-400",
    proposal: "bg-violet-500/15 text-violet-400",
    negotiation: "bg-amber-500/15 text-amber-400",
    won: "bg-emerald-500/15 text-emerald-400",
    lost: "bg-red-500/15 text-red-400",
};

const STAGE_BORDER = {
    lead: "border-slate-500/30",
    qualified: "border-blue-500/30",
    proposal: "border-violet-500/30",
    negotiation: "border-amber-500/30",
    won: "border-emerald-500/30",
    lost: "border-red-500/30",
};

const FALLBACK_BG = STAGE_BG.lead;
const FALLBACK_BORDER = STAGE_BORDER.lead;

export function stageBadge(stage) {
    return STAGE_BG[stage] ?? FALLBACK_BG;
}

export function stageBadgeBordered(stage) {
    return `${stageBadge(stage)} border ${STAGE_BORDER[stage] ?? FALLBACK_BORDER}`;
}
