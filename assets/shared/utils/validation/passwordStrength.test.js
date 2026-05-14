import { describe, it, expect } from "vitest";
import { calculatePasswordStrength } from "./passwordStrength.js";

describe("calculatePasswordStrength", () => {
    it("returns 0 for empty/falsy password", () => {
        expect(calculatePasswordStrength("")).toBe(0);
        expect(calculatePasswordStrength(null)).toBe(0);
    });

    it("returns 0 for a very short password with no special criteria", () => {
        expect(calculatePasswordStrength("abc")).toBe(0);
    });

    it("returns higher score for longer passwords with more criteria", () => {
        const score = calculatePasswordStrength("Abcdef12!");
        expect(score).toBeGreaterThanOrEqual(3);
    });

    it("returns 5 for a very strong password", () => {
        // >=20 chars, uppercase, digit, special
        expect(calculatePasswordStrength("SecureP@ssw0rd!!xxxx1")).toBe(5);
    });

    it("accounts for length thresholds at 12 and 20", () => {
        const short = calculatePasswordStrength("Abcde1!");   // < 12 chars
        const medium = calculatePasswordStrength("Abcdefgh1!zz"); // >= 12 chars
        expect(medium).toBeGreaterThan(short);
    });
});
