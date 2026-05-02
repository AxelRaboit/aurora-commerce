import { describe, expect, it } from "vitest";
import { formatMoney } from "@ecommerce/utils/formatMoney.js";

describe("formatMoney", () => {
    it("formats whole numbers with two decimals and symbol", () => {
        const result = formatMoney(12, "€");
        expect(result).toMatch(/12[.,]00/);
        expect(result).toContain("€");
    });

    it("treats null/undefined amount as zero", () => {
        expect(formatMoney(null, "€")).toMatch(/0[.,]00 €/);
        expect(formatMoney(undefined, "USD")).toMatch(/0[.,]00 USD/);
    });

    it("trims trailing space when symbol is missing", () => {
        const result = formatMoney(5);
        expect(result).not.toMatch(/\s$/);
        expect(result).toMatch(/5[.,]00/);
    });

    it("rounds to exactly two decimals", () => {
        expect(formatMoney(1.005, "€")).toMatch(/1[.,]0[01] €/);
        expect(formatMoney(99.999, "€")).toMatch(/100[.,]00 €/);
    });
});
