import { describe, it, expect } from "vitest";
import { seoCounterClass } from "./seoCounter.js";

describe("seoCounterClass", () => {
    it("returns text-muted when length is 0", () => {
        expect(seoCounterClass(0, 60)).toBe("text-muted");
    });

    it("returns text-green-500 when length is well below max", () => {
        expect(seoCounterClass(30, 60)).toBe("text-green-500");
    });

    it("returns text-amber-500 when length is between 85% and 100% of max", () => {
        expect(seoCounterClass(55, 60)).toBe("text-amber-500");
    });

    it("returns text-red-500 when length exceeds max", () => {
        expect(seoCounterClass(70, 60)).toBe("text-red-500");
    });

    it("returns text-amber-500 exactly at the max", () => {
        expect(seoCounterClass(60, 60)).toBe("text-amber-500");
    });
});
