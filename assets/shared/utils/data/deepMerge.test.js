import { describe, it, expect } from "vitest";
import { deepMerge } from "./deepMerge.js";

describe("deepMerge", () => {
    it("merges flat objects, source wins on conflict", () => {
        expect(deepMerge({ a: 1, b: 2 }, { b: 99, c: 3 })).toEqual({
            a: 1,
            b: 99,
            c: 3,
        });
    });

    it("recursively merges nested objects", () => {
        expect(
            deepMerge({ x: { a: 1, b: 2 } }, { x: { b: 99, c: 3 } }),
        ).toEqual({ x: { a: 1, b: 99, c: 3 } });
    });

    it("replaces arrays instead of merging them", () => {
        expect(deepMerge({ tags: [1, 2] }, { tags: [3] })).toEqual({
            tags: [3],
        });
    });

    it("returns source when target is not a plain object", () => {
        expect(deepMerge(null, { a: 1 })).toEqual({ a: 1 });
        expect(deepMerge("string", { a: 1 })).toEqual({ a: 1 });
    });

    it("returns target when source is not a plain object", () => {
        expect(deepMerge({ a: 1 }, null)).toEqual({ a: 1 });
    });
});
