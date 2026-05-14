import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppLink from "./AppLink.vue";

describe("AppLink", () => {
    it("renders an anchor with the correct href", () => {
        const wrapper = mount(AppLink, { props: { href: "https://example.com" } });
        expect(wrapper.find("a").attributes("href")).toBe("https://example.com");
    });

    it("applies primary variant classes by default", () => {
        const wrapper = mount(AppLink, { props: { href: "/foo" } });
        expect(wrapper.find("a").classes()).toContain("text-primary");
        expect(wrapper.find("a").classes()).toContain("underline");
    });

    it("applies muted variant classes", () => {
        const wrapper = mount(AppLink, { props: { href: "/foo", variant: "muted" } });
        expect(wrapper.find("a").classes()).toContain("text-muted");
    });

    it("applies front variant classes", () => {
        const wrapper = mount(AppLink, { props: { href: "/foo", variant: "front" } });
        expect(wrapper.find("a").classes()).toContain("font-medium");
    });

    it("sets target and rel attributes for _blank", () => {
        const wrapper = mount(AppLink, { props: { href: "/foo", target: "_blank" } });
        const anchor = wrapper.find("a");
        expect(anchor.attributes("target")).toBe("_blank");
        expect(anchor.attributes("rel")).toBe("noopener");
    });

    it("applies sm size class", () => {
        const wrapper = mount(AppLink, { props: { href: "/foo", size: "sm" } });
        expect(wrapper.find("a").classes()).toContain("text-sm");
    });
});
