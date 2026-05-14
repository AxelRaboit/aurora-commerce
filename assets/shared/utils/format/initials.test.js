import { describe, it, expect } from "vitest";
import { initials } from "./initials.js";

describe("initials", () => {
    it("returns first + last initial of a full name", () => {
        expect(initials({ name: "Jean Dupont" })).toBe("JD");
    });

    it("returns first two letters for a single-word name", () => {
        expect(initials({ name: "Alice" })).toBe("AL");
    });

    it("uses firstName + lastName when name is empty", () => {
        expect(initials({ firstName: "Marie", lastName: "Curie" })).toBe("MC");
    });

    it("falls back to first letter of email", () => {
        expect(initials({ email: "axel@example.com" })).toBe("A");
    });

    it("returns '?' when no data is provided", () => {
        expect(initials({})).toBe("?");
        expect(initials()).toBe("?");
    });
});
