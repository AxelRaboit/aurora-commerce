/**
 * Per-section visual theme for the sidemenu — colour-codes each module
 * group with a subtle tinted background, a 3 px left accent strip, and
 * a matching tinted label so scanning the nav feels structured even
 * with 20+ entries.
 *
 * Mirrors the approach used on the Budget page (see
 * `useBudgetSectionTheme`) so the same eye habits transfer.
 *
 * The tints stay light (10 % alpha background + 400 text + 500 border)
 * — the sidemenu is a neutral surface and we don't want the colour
 * pulling focus away from the active item. Tailwind JIT picks up the
 * full class strings present in this map; no safelist needed.
 *
 * Section IDs are the same `NavSection` ids the PHP modules emit. Any
 * id not in the map falls back to a neutral slate/line theme.
 */
const SECTION_THEMES = {
    general:          { bg: "bg-slate-500/10",   border: "border-l-slate-500",   text: "text-slate-300" },
    platform:         { bg: "bg-indigo-500/10",  border: "border-l-indigo-500",  text: "text-indigo-400" },
    vault:            { bg: "bg-stone-500/10",   border: "border-l-stone-500",   text: "text-stone-300" },
    configuration:    { bg: "bg-zinc-500/10",    border: "border-l-zinc-500",    text: "text-zinc-300" },
    notes:            { bg: "bg-yellow-500/10",  border: "border-l-yellow-500",  text: "text-yellow-400" },
    personal_finance: { bg: "bg-emerald-500/10", border: "border-l-emerald-500", text: "text-emerald-400" },
    editorial:        { bg: "bg-rose-500/10",    border: "border-l-rose-500",    text: "text-rose-400" },
    ged:              { bg: "bg-lime-500/10",    border: "border-l-lime-500",    text: "text-lime-400" },
    planning:         { bg: "bg-cyan-500/10",    border: "border-l-cyan-500",    text: "text-cyan-400" },
    crm:              { bg: "bg-sky-500/10",     border: "border-l-sky-500",     text: "text-sky-400" },
    erp:              { bg: "bg-teal-500/10",    border: "border-l-teal-500",    text: "text-teal-400" },
    ecommerce:        { bg: "bg-purple-500/10",  border: "border-l-purple-500",  text: "text-purple-400" },
    billing:          { bg: "bg-amber-500/10",   border: "border-l-amber-500",   text: "text-amber-400" },
    photo:            { bg: "bg-fuchsia-500/10", border: "border-l-fuchsia-500", text: "text-fuchsia-400" },
    media:            { bg: "bg-pink-500/10",    border: "border-l-pink-500",    text: "text-pink-400" },
    project:          { bg: "bg-blue-500/10",    border: "border-l-blue-500",    text: "text-blue-400" },
    hr:               { bg: "bg-green-500/10",   border: "border-l-green-500",   text: "text-green-400" },
    pdfform:          { bg: "bg-red-500/10",     border: "border-l-red-500",     text: "text-red-400" },
    dev:              { bg: "bg-orange-500/10",  border: "border-l-orange-500",  text: "text-orange-400" },
    assistant:        { bg: "bg-violet-500/10",  border: "border-l-violet-500",  text: "text-violet-400" },
};

const FALLBACK_THEME = {
    bg: "bg-surface-2/40",
    border: "border-l-line",
    text: "text-muted",
};

export function useSidemenuSectionTheme() {
    function resolve(sectionId) {
        return SECTION_THEMES[sectionId] ?? FALLBACK_THEME;
    }

    function headerClasses(sectionId) {
        const theme = resolve(sectionId);
        return `${theme.bg} border-l-[3px] ${theme.border} rounded-r-md`;
    }

    function labelClasses(sectionId) {
        return resolve(sectionId).text;
    }

    return { headerClasses, labelClasses };
}
