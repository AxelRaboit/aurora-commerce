import { describe, it, expect } from "vitest";
import {
    CURRENCY_OPTIONS,
    CURRENCY_BY_CODE,
    DEFAULT_CURRENCY,
    symbolFor,
} from "./currencies.js";

describe("CURRENCY_OPTIONS", () => {
    it("contains EUR and USD entries", () => {
        const codes = CURRENCY_OPTIONS.map((c) => c.value);
        expect(codes).toContain("EUR");
        expect(codes).toContain("USD");
    });

    it("each entry has value, symbol and label", () => {
        for (const entry of CURRENCY_OPTIONS) {
            expect(entry).toHaveProperty("value");
            expect(entry).toHaveProperty("symbol");
            expect(entry).toHaveProperty("label");
        }
    });
});

describe("CURRENCY_BY_CODE", () => {
    it("indexes currencies by their code", () => {
        expect(CURRENCY_BY_CODE["EUR"].symbol).toBe("€");
        expect(CURRENCY_BY_CODE["GBP"].symbol).toBe("£");
    });
});

describe("DEFAULT_CURRENCY", () => {
    it("is EUR", () => {
        expect(DEFAULT_CURRENCY).toBe("EUR");
    });
});

describe("symbolFor", () => {
    it("returns the symbol for a known currency code", () => {
        expect(symbolFor("EUR")).toBe("€");
        expect(symbolFor("CHF")).toBe("CHF");
    });

    it("returns the code itself for an unknown currency", () => {
        expect(symbolFor("XYZ")).toBe("XYZ");
    });
});
