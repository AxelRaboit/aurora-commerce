import { describe, it, expect } from "vitest";
import { required, email, url, compose } from "./validators.js";

describe("required", () => {
    const validate = required("This field is required");

    it("returns the message for null/undefined", () => {
        expect(validate(null)).toBe("This field is required");
        expect(validate(undefined)).toBe("This field is required");
    });

    it("returns the message for empty string", () => {
        expect(validate("   ")).toBe("This field is required");
    });

    it("returns the message for empty array", () => {
        expect(validate([])).toBe("This field is required");
    });

    it("returns null for a valid value", () => {
        expect(validate("hello")).toBeNull();
        expect(validate(0)).toBeNull();
    });
});

describe("email", () => {
    const validate = email("Invalid email");

    it("returns null for empty value (not required)", () => {
        expect(validate("")).toBeNull();
        expect(validate(null)).toBeNull();
    });

    it("returns the message for a malformed email", () => {
        expect(validate("bad@")).toBe("Invalid email");
    });

    it("returns null for a valid email", () => {
        expect(validate("user@example.com")).toBeNull();
    });
});

describe("url", () => {
    const validate = url("Invalid URL");

    it("returns null for empty value", () => {
        expect(validate("")).toBeNull();
    });

    it("returns the message for an invalid URL", () => {
        expect(validate("not-a-url")).toBe("Invalid URL");
    });

    it("returns null for a valid URL", () => {
        expect(validate("https://example.com")).toBeNull();
    });
});

describe("compose", () => {
    it("runs validators in order and returns the first error", () => {
        const validate = compose(required("Required"), email("Bad email"));
        expect(validate("")).toBe("Required");
        expect(validate("bad@")).toBe("Bad email");
        expect(validate("ok@example.com")).toBeNull();
    });
});
