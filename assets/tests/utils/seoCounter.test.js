import { describe, expect, it } from "vitest";
import { seoCounterClass } from "@/shared/utils/seo/seoCounter.js";

describe("seoCounterClass", () => {
    it("returns muted when length is zero", () => {
        expect(seoCounterClass(0, 60)).toBe("text-muted");
    });

    it("returns green up to 85% of max", () => {
        expect(seoCounterClass(1, 60)).toBe("text-green-500");
        expect(seoCounterClass(51, 60)).toBe("text-green-500"); // 85% of 60 = 51
    });

    it("returns amber between 85% and 100%", () => {
        expect(seoCounterClass(52, 60)).toBe("text-amber-500");
        expect(seoCounterClass(60, 60)).toBe("text-amber-500");
    });

    it("returns red over the max", () => {
        expect(seoCounterClass(61, 60)).toBe("text-red-500");
        expect(seoCounterClass(200, 60)).toBe("text-red-500");
    });
});
