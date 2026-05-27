import { describe, expect, it } from "vitest";
import { ref, nextTick } from "vue";
import { usePasswordStrength } from "@tools/backend/vault/composables/usePasswordStrength.js";

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

describe("usePasswordStrength", () => {
    it("returns score 0, empty label and empty color for empty password", () => {
        const password = ref("");
        const { score, strengthLabel, strengthColor } =
            usePasswordStrength(password);

        expect(score.value).toBe(0);
        expect(strengthLabel.value).toBe("");
        expect(strengthColor.value).toBe("");
    });

    it("returns score > 0 for a strong password", () => {
        const password = ref("Str0ng!P@ssw0rd!XYZ");
        const { score } = usePasswordStrength(password);

        expect(score.value).toBeGreaterThan(0);
    });

    it("strengthLabel matches STRENGTH_LABELS at the computed score", () => {
        const password = ref("Str0ng!P@ssw0rd!XYZ");
        const { score, strengthLabel } = usePasswordStrength(password);

        expect(strengthLabel.value).toBe(STRENGTH_LABELS[score.value]);
    });

    it("strengthColor matches STRENGTH_COLORS at the computed score", () => {
        const password = ref("Str0ng!P@ssw0rd!XYZ");
        const { score, strengthColor } = usePasswordStrength(password);

        expect(strengthColor.value).toBe(STRENGTH_COLORS[score.value]);
    });

    it("score is reactive and updates when the ref changes", async () => {
        const password = ref("");
        const { score } = usePasswordStrength(password);

        expect(score.value).toBe(0);

        password.value = "Str0ng!P@ssw0rd!XYZ";
        await nextTick();

        expect(score.value).toBeGreaterThan(0);
    });

    it("label and color are reactive and update when the ref changes", async () => {
        const password = ref("");
        const { strengthLabel, strengthColor } = usePasswordStrength(password);

        expect(strengthLabel.value).toBe("");
        expect(strengthColor.value).toBe("");

        password.value = "Str0ng!P@ssw0rd!XYZ";
        await nextTick();

        expect(strengthLabel.value).not.toBe("");
        expect(strengthColor.value).not.toBe("");
    });
});
