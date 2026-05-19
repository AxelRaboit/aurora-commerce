import { computed } from "vue";
import { calculatePasswordStrength } from "@shared/utils/validation/passwordStrength.js";

const STRENGTH_LABELS = [
    "",
    "vault.setup.strength.weak",
    "vault.setup.strength.fair",
    "vault.setup.strength.good",
    "vault.setup.strength.strong",
    "vault.setup.strength.very_strong",
];

const STRENGTH_COLORS = [
    "",
    "bg-red-500",
    "bg-orange-500",
    "bg-yellow-500",
    "bg-emerald-500",
    "bg-emerald-600",
];

/**
 * @param {import('vue').Ref<string>} passwordRef
 */
export function usePasswordStrength(passwordRef) {
    const score = computed(() => calculatePasswordStrength(passwordRef.value));
    const strengthLabel = computed(() => STRENGTH_LABELS[score.value] ?? "");
    const strengthColor = computed(() => STRENGTH_COLORS[score.value] ?? "");

    return { score, strengthLabel, strengthColor };
}
