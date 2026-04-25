import { describe, it, expect } from "vitest";
import { truncate } from "./truncate.js";

describe("truncate", () => {
    it("returns empty string for null", () => {
        expect(truncate(null, 10)).toBe("");
    });

    it("returns empty string for empty string", () => {
        expect(truncate("", 10)).toBe("");
    });

    it("returns string as-is when shorter than limit", () => {
        expect(truncate("Hello", 10)).toBe("Hello");
    });

    it("returns string as-is when exactly at limit", () => {
        expect(truncate("Hello", 5)).toBe("Hello");
    });

    it("truncates and appends ellipsis when over limit", () => {
        expect(truncate("Hello World", 5)).toBe("Hello…");
    });

    it("truncates at correct character position", () => {
        expect(truncate("abcdefgh", 4)).toBe("abcd…");
    });
});
