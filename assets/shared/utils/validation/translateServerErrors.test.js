import { describe, it, expect } from "vitest";
import { translateServerErrors } from "./translateServerErrors.js";

describe("translateServerErrors", () => {
    const t = (key) => `[${key}]`;

    it("translates values that look like translation keys (contain a dot)", () => {
        const result = translateServerErrors(t, { slug: "photo.galleries.errors.slug_taken" });
        expect(result.slug).toBe("[photo.galleries.errors.slug_taken]");
    });

    it("passes through values without a dot unchanged", () => {
        const result = translateServerErrors(t, { name: "Already taken" });
        expect(result.name).toBe("Already taken");
    });

    it("returns an empty object when errors is null or undefined", () => {
        expect(translateServerErrors(t, null)).toEqual({});
        expect(translateServerErrors(t, undefined)).toEqual({});
    });

    it("handles mixed entries correctly", () => {
        const errors = { field1: "some.translation.key", field2: "Literal message" };
        const result = translateServerErrors(t, errors);
        expect(result.field1).toBe("[some.translation.key]");
        expect(result.field2).toBe("Literal message");
    });
});
