import { describe, expect, it } from "vitest";
import { slugify, slugifyIfEmpty } from "@/shared/utils/format/slugify.js";

describe("slugify", () => {
    it("returns empty string for null/undefined", () => {
        expect(slugify(null)).toBe("");
        expect(slugify(undefined)).toBe("");
    });

    it("lowercases and replaces spaces", () => {
        expect(slugify("Hello World")).toBe("hello-world");
    });

    it("strips diacritics", () => {
        expect(slugify("À l'éCole")).toBe("a-l-ecole");
    });

    it("collapses non-alphanumeric runs into a single dash", () => {
        expect(slugify("foo!!!  bar___baz")).toBe("foo-bar-baz");
    });

    it("trims leading and trailing dashes", () => {
        expect(slugify("--foo--")).toBe("foo");
    });
});

describe("slugifyIfEmpty", () => {
    it("returns currentSlug unchanged when non-empty", () => {
        expect(slugifyIfEmpty("kept", "Anything Else")).toBe("kept");
    });

    it("derives slug from source when currentSlug is empty", () => {
        expect(slugifyIfEmpty("", "Hello World")).toBe("hello-world");
    });

    it("treats null/undefined currentSlug as empty", () => {
        expect(slugifyIfEmpty(null, "Hello")).toBe("hello");
        expect(slugifyIfEmpty(undefined, "Hello")).toBe("hello");
    });
});
