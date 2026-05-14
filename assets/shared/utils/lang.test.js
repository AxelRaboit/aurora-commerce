import { describe, it, expect } from "vitest";
import { Locale, DEFAULT_LOCALES, LOCALE_LABELS } from "./lang.js";

describe("Locale", () => {
    it("contains the four supported locale codes", () => {
        expect(Locale.Fr).toBe("fr");
        expect(Locale.En).toBe("en");
        expect(Locale.Es).toBe("es");
        expect(Locale.De).toBe("de");
    });

    it("is frozen (immutable)", () => {
        expect(Object.isFrozen(Locale)).toBe(true);
    });
});

describe("DEFAULT_LOCALES", () => {
    it("contains all locale values", () => {
        expect(DEFAULT_LOCALES).toContain("fr");
        expect(DEFAULT_LOCALES).toContain("en");
        expect(DEFAULT_LOCALES).toHaveLength(4);
    });
});

describe("LOCALE_LABELS", () => {
    it("maps each locale code to its human-readable label", () => {
        expect(LOCALE_LABELS["fr"]).toBe("Français");
        expect(LOCALE_LABELS["en"]).toBe("English");
        expect(LOCALE_LABELS["es"]).toBe("Español");
        expect(LOCALE_LABELS["de"]).toBe("Deutsch");
    });
});
