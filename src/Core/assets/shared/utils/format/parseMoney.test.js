import { describe, it, expect } from "vitest";
import { parseMoney } from "./parseMoney.js";

describe("parseMoney", () => {
    it("parses dot-decimal format", () => {
        expect(parseMoney("19.90")).toBe(1990);
    });

    it("parses comma-decimal format", () => {
        expect(parseMoney("5,00")).toBe(500);
    });

    it("parses European format with dot-thousands and comma-decimal", () => {
        expect(parseMoney("1.200,00")).toBe(120000);
    });

    it("parses thousands-only format with no decimal part", () => {
        expect(parseMoney("1.000")).toBe(100000);
    });

    it("returns null for empty or invalid input", () => {
        expect(parseMoney("")).toBeNull();
        expect(parseMoney(null)).toBeNull();
        expect(parseMoney("abc")).toBeNull();
    });
});
