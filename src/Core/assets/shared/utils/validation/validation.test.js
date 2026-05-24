import { describe, it, expect } from "vitest";
import { isValidEmail, EMAIL_REGEX } from "./validation.js";

describe("EMAIL_REGEX", () => {
    it("matches valid email addresses", () => {
        expect(EMAIL_REGEX.test("user@example.com")).toBe(true);
        expect(EMAIL_REGEX.test("a.b+c@sub.domain.org")).toBe(true);
    });

    it("rejects invalid email addresses", () => {
        expect(EMAIL_REGEX.test("no-at-sign")).toBe(false);
        expect(EMAIL_REGEX.test("@nodomain")).toBe(false);
    });
});

describe("isValidEmail", () => {
    it("returns true for valid emails", () => {
        expect(isValidEmail("hello@world.io")).toBe(true);
    });

    it("returns false for invalid emails", () => {
        expect(isValidEmail("not-an-email")).toBe(false);
    });

    it("trims whitespace before validating", () => {
        expect(isValidEmail("  user@example.com  ")).toBe(true);
    });
});
