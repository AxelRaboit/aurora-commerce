import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";

/**
 * Sort + deadline-derived helpers for the goals grid. Keeps the
 * comparator + the "X months remaining → Y/month contribution"
 * calculation out of the SFC so the component stays presentational.
 *
 * Sort keys:
 *   - "deadline" (default): earliest deadline first; null deadlines
 *     are pushed to the bottom
 *   - "progress": highest progress % first
 *   - "amount":   highest targetAmount first
 */
export function useGoalsSort(goalsRef) {
    const { t } = useI18n();
    const sortBy = ref("deadline");

    const sortOptions = computed(() => [
        { value: "deadline", label: t("personal_finance.goals.sort.deadline") },
        { value: "progress", label: t("personal_finance.goals.sort.progress") },
        { value: "amount", label: t("personal_finance.goals.sort.amount") },
    ]);

    const sortedGoals = computed(() => {
        const out = [...goalsRef.value];
        if (sortBy.value === "progress") {
            out.sort((a, b) => b.progress - a.progress);
        } else if (sortBy.value === "amount") {
            out.sort(
                (a, b) =>
                    parseFloat(b.targetAmount) - parseFloat(a.targetAmount),
            );
        } else {
            out.sort((a, b) => {
                if (!a.deadline && !b.deadline) return 0;
                if (!a.deadline) return 1;
                if (!b.deadline) return -1;
                return a.deadline.localeCompare(b.deadline);
            });
        }
        return out;
    });

    return { sortBy, sortOptions, sortedGoals };
}

/**
 * Returns the integer number of whole months between today (UTC) and
 * the deadline (YYYY-MM-DD), or null when no deadline is set.
 * Negative when deadline already passed.
 */
export function monthsRemaining(deadline) {
    if (!deadline) return null;
    const now = new Date();
    const target = new Date(deadline);
    return (
        (target.getFullYear() - now.getFullYear()) * 12 +
        (target.getMonth() - now.getMonth())
    );
}

/**
 * Monthly amount the user should save to hit the deadline, formatted
 * as a 2-decimal string. Returns null when deadline missing or
 * already passed. Returns "0.00" when the goal is already complete
 * (remaining ≤ 0).
 */
export function monthlyContribution(goal) {
    const months = monthsRemaining(goal.deadline);
    if (months === null || months <= 0) return null;
    const remaining = parseFloat(goal.remainingAmount);
    if (remaining <= 0) return "0.00";
    return (remaining / months).toFixed(2);
}
