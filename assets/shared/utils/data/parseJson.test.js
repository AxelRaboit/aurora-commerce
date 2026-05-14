import { describe, it, expect } from "vitest";
import { parseJson } from "./parseJson.js";

describe("parseJson", () => {
    it("parses a valid JSON string", () => {
        expect(parseJson('{"a":1}', {})).toEqual({ a: 1 });
    });

    it("returns the fallback for invalid JSON", () => {
        expect(parseJson("not-json", [])).toEqual([]);
    });

    it("returns the fallback for null/undefined", () => {
        expect(parseJson(null, "default")).toBe("default");
        expect(parseJson(undefined, 42)).toBe(42);
    });

    it("returns the object as-is when already an object (idempotent)", () => {
        const obj = { x: 1 };
        expect(parseJson(obj, {})).toBe(obj);
    });

    it("parses a JSON array string", () => {
        expect(parseJson("[1,2,3]", [])).toEqual([1, 2, 3]);
    });
});
