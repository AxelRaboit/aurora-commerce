/**
 * Per-section visual theme for the Budget page — colour-codes the
 * five budget buckets so they are scannable at a glance. Tints stay
 * subtle (10 % alpha background + 4 px left accent) so the page
 * keeps its neutral surface tone overall.
 *
 * Tailwind JIT picks up the full class strings present in this map,
 * so no safelist is needed.
 */
const SECTION_THEMES = {
    income: { bg: "bg-emerald-500/10", border: "border-l-emerald-500", text: "text-emerald-400" },
    savings: { bg: "bg-sky-500/10", border: "border-l-sky-500", text: "text-sky-400" },
    fixed_charges: { bg: "bg-amber-500/10", border: "border-l-amber-500", text: "text-amber-400" },
    expenses: { bg: "bg-rose-500/10", border: "border-l-rose-500", text: "text-rose-400" },
    debt: { bg: "bg-violet-500/10", border: "border-l-violet-500", text: "text-violet-400" },
};

const FALLBACK_THEME = { bg: "bg-surface-2/40", border: "border-l-line", text: "text-primary" };

export function useBudgetSectionTheme() {
    function resolve(section) {
        return SECTION_THEMES[section] ?? FALLBACK_THEME;
    }

    function headerClasses(section) {
        const theme = resolve(section);
        return `${theme.bg} border-l-4 ${theme.border}`;
    }

    function titleClasses(section) {
        return resolve(section).text;
    }

    return { headerClasses, titleClasses };
}
