import { describe, expect, it } from "vitest";
import { parseJsonLd, buildArticleJsonLd } from "@/shared/utils/seo/jsonLd.js";

describe("parseJsonLd", () => {
    it("returns empty marker for blank string", () => {
        expect(parseJsonLd("")).toEqual({
            value: null,
            error: null,
            empty: true,
        });
        expect(parseJsonLd("   ")).toEqual({
            value: null,
            error: null,
            empty: true,
        });
        expect(parseJsonLd(null)).toEqual({
            value: null,
            error: null,
            empty: true,
        });
    });

    it("parses a valid JSON object", () => {
        const result = parseJsonLd('{"@type": "Article"}');
        expect(result.value).toEqual({ "@type": "Article" });
        expect(result.error).toBeNull();
        expect(result.empty).toBe(false);
    });

    it("rejects arrays as not-object", () => {
        const result = parseJsonLd("[1, 2, 3]");
        expect(result.error).toBe("not-object");
        expect(result.value).toBeNull();
    });

    it("rejects primitives as not-object", () => {
        expect(parseJsonLd("42").error).toBe("not-object");
        expect(parseJsonLd('"string"').error).toBe("not-object");
        expect(parseJsonLd("null").error).toBe("not-object");
    });

    it("returns parser error message for invalid JSON", () => {
        const result = parseJsonLd("{not valid");
        expect(result.value).toBeNull();
        expect(result.error).toBeTruthy();
        expect(result.error).not.toBe("not-object");
    });
});

describe("buildArticleJsonLd", () => {
    it("uses schema.org Article context", () => {
        const result = buildArticleJsonLd({ title: "X" });
        expect(result["@context"]).toBe("https://schema.org");
        expect(result["@type"]).toBe("Article");
    });

    it("strips undefined optional fields", () => {
        const result = buildArticleJsonLd({ title: "X", description: "Y" });
        expect(result.headline).toBe("X");
        expect(result.description).toBe("Y");
        expect(result).not.toHaveProperty("image");
        expect(result).not.toHaveProperty("datePublished");
    });

    it("includes image as array when imageUrl provided", () => {
        const result = buildArticleJsonLd({ imageUrl: "https://x/y.png" });
        expect(result.image).toEqual(["https://x/y.png"]);
    });

    it("includes datePublished when provided", () => {
        const result = buildArticleJsonLd({ datePublished: "2024-01-01" });
        expect(result.datePublished).toBe("2024-01-01");
    });

    it("defaults headline and description to empty string", () => {
        const result = buildArticleJsonLd({});
        expect(result.headline).toBe("");
        expect(result.description).toBe("");
    });
});
