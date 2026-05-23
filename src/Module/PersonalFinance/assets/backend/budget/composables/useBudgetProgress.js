/**
 * Pure-functional helpers for the Budgets page progress UI :
 * percentages, overrun detection, and diff colour-pill.
 *
 * No reactive state — these are deterministic helpers extracted from
 * the SFC so the view stays thin (cf. convention_sfc_thin_presentation)
 * and the rules become unit-testable.
 */
export function useBudgetProgress() {
    /** Capped percentage, 0–100 — used for the actual visual bar width. */
    function progressPct(item) {
        const expected = parseFloat(item.expected ?? "0");
        const actual = parseFloat(item.actual ?? "0");
        if (!expected || expected <= 0) return 0;
        return Math.min(100, Math.round((actual / expected) * 100));
    }

    /** Raw ratio, not capped — handy if a caller wants to show "120 %" textually. */
    function progressRatio(item) {
        const expected = parseFloat(item.expected ?? "0");
        const actual = parseFloat(item.actual ?? "0");
        if (!expected || expected <= 0) return 0;
        return actual / expected;
    }

    /** True when actual > expected — drives the rose progress bar + warning icon. */
    function isOverrun(item) {
        return progressRatio(item) > 1;
    }

    /** Colour-pill classes for the diff cell — green / red / neutral. */
    function diffPillClasses(item) {
        const diff = parseFloat(item.diff ?? "0");
        if (diff > 0) return "bg-emerald-500/15 text-emerald-400";
        if (diff < 0) return "bg-rose-500/15 text-rose-400";
        return "bg-surface-2 text-muted";
    }

    return { progressPct, progressRatio, isOverrun, diffPillClasses };
}
