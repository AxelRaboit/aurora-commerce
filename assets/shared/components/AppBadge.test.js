import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppBadge from "./AppBadge.vue";

describe("AppBadge", () => {
    it("renders slot content", () => {
        const wrapper = mount(AppBadge, {
            slots: { default: "Published" },
        });
        expect(wrapper.text()).toContain("Published");
    });

    it("defaults to gray color", () => {
        const wrapper = mount(AppBadge);
        expect(wrapper.find("span").classes()).toContain("bg-surface-2");
        expect(wrapper.find("span").classes()).toContain("text-secondary");
    });

    it("applies accent color", () => {
        const wrapper = mount(AppBadge, { props: { color: "accent" } });
        expect(wrapper.find("span").classes()).toContain("bg-accent-600/15");
        expect(wrapper.find("span").classes()).toContain("text-accent-400");
    });

    it("applies rose color", () => {
        const wrapper = mount(AppBadge, { props: { color: "rose" } });
        expect(wrapper.find("span").classes()).toContain("bg-rose-500/15");
        expect(wrapper.find("span").classes()).toContain("text-rose-400");
    });

    it("applies emerald color", () => {
        const wrapper = mount(AppBadge, { props: { color: "emerald" } });
        expect(wrapper.find("span").classes()).toContain("bg-emerald-500/15");
        expect(wrapper.find("span").classes()).toContain("text-emerald-400");
    });

    it("falls back to gray for unknown color", () => {
        const wrapper = mount(AppBadge, { props: { color: "unknown" } });
        expect(wrapper.find("span").classes()).toContain("bg-surface-2");
    });

    it("always has base pill classes", () => {
        const wrapper = mount(AppBadge);
        const classes = wrapper.find("span").classes();
        expect(classes).toContain("inline-flex");
        expect(classes).toContain("rounded-full");
        expect(classes).toContain("text-xs");
    });
});
