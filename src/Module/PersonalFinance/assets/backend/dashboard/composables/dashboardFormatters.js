/**
 * Pure presentation helpers for the dashboard. No state, no side
 * effects — extracted so the SFC stays declarative and these can be
 * unit-tested in isolation.
 */

/**
 * Builds the `d` attribute of an inline SVG path from a daily expense
 * series. Returns "" when the series is empty so the <path> element
 * just collapses.
 *
 * @param {Array<{date: string, expense: string}>} points
 * @param {number} [width=200]
 * @param {number} [height=40]
 */
export function buildSparklinePath(points, width = 200, height = 40) {
    if (!points || points.length === 0) return "";
    const values = points.map((p) => parseFloat(p.expense));
    const max = Math.max(1, ...values);
    const step = width / Math.max(1, values.length - 1);
    return values
        .map((v, i) => {
            const x = (i * step).toFixed(2);
            const y = (height - (v / max) * height).toFixed(2);
            return `${0 === i ? "M" : "L"}${x},${y}`;
        })
        .join(" ");
}

/**
 * Returns a Tailwind text colour class for a month-over-month delta.
 * Switches polarity for "expense-like" metrics where a positive delta
 * (more spending) is bad, vs "income-like" where positive is good.
 */
export function deltaClass(delta, expenseIsBad = true) {
    if (delta === null || delta === undefined) return "text-muted";
    if (delta === 0) return "text-muted";
    if (expenseIsBad) return delta > 0 ? "text-rose-400" : "text-emerald-400";
    return delta > 0 ? "text-emerald-400" : "text-rose-400";
}

export function formatDelta(delta) {
    if (delta === null || delta === undefined) return "—";
    const sign = delta > 0 ? "+" : "";
    return `${sign}${delta}%`;
}

export function signedAmount(tx) {
    const sign = tx.type === "income" ? "+" : "-";
    return `${sign}${tx.amount}`;
}
