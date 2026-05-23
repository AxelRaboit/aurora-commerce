/**
 * Pure presentation helpers for the Statistics page. SVG path
 * builders + delta presentation helpers. No state, no side
 * effects — kept here so the SFC stays declarative and these can
 * be unit-tested in isolation.
 */

/**
 * Build the `d` attribute of an inline SVG line path from a daily /
 * monthly series. Empty series → empty path, which collapses to a
 * blank chart.
 *
 * @param {Array<{ [key: string]: string | number }>} points
 * @param {string} valueKey — property name carrying the numeric value
 * @param {number} [width=200]
 * @param {number} [height=40]
 */
export function buildLinePath(points, valueKey, width = 200, height = 40) {
    if (!points || points.length === 0) return "";
    const values = points.map((p) => parseFloat(p[valueKey] ?? 0));
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
 * Compute the bar geometry for a monthly income/expense chart. Each
 * month gets two bars (income / expense) side by side, centred on
 * the month slot.
 *
 * @param {Array<{ month: string, income: string, expense: string }>} months
 * @param {number} [width=600]
 * @param {number} [height=160]
 * @returns {{ bars: Array<{x: number, y: number, width: number, height: number, kind: 'income' | 'expense'}>, max: number, slotWidth: number, labels: Array<{x: number, label: string}> }}
 */
export function buildMonthlyBars(months, width = 600, height = 160) {
    if (!months || months.length === 0) {
        return { bars: [], max: 1, slotWidth: 0, labels: [] };
    }
    const values = months.flatMap((m) => [parseFloat(m.income), parseFloat(m.expense)]);
    const max = Math.max(1, ...values);
    const slotWidth = width / months.length;
    const barWidth = Math.max(6, slotWidth * 0.35);
    const gap = slotWidth * 0.08;

    const bars = [];
    const labels = [];
    months.forEach((m, i) => {
        const center = slotWidth * (i + 0.5);
        const incomeH = (parseFloat(m.income) / max) * height;
        const expenseH = (parseFloat(m.expense) / max) * height;
        bars.push({ x: center - barWidth - gap / 2, y: height - incomeH, width: barWidth, height: incomeH, kind: "income" });
        bars.push({ x: center + gap / 2, y: height - expenseH, width: barWidth, height: expenseH, kind: "expense" });
        labels.push({ x: center, label: m.month.slice(5) }); // MM
    });

    return { bars, max, slotWidth, labels };
}

/**
 * Returns a Tailwind text colour class for a month-over-month delta.
 * Switches polarity for "expense-like" metrics where positive (more
 * spending) is bad, vs "income-like" where positive is good.
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
