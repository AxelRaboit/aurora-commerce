import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppTab from "./AppTab.vue";

describe("AppTab", () => {
    it("renders a button element", () => {
        const wrapper = mount(AppTab);
        expect(wrapper.find("button").exists()).toBe(true);
    });

    it("applies active pill accent classes when active is true", () => {
        const wrapper = mount(AppTab, { props: { active: true } });
        expect(wrapper.find("button").classes()).toContain("bg-accent-600/15");
        expect(wrapper.find("button").classes()).toContain("text-accent-400");
    });

    it("applies inactive classes when active is false", () => {
        const wrapper = mount(AppTab, { props: { active: false } });
        expect(wrapper.find("button").classes()).toContain("text-secondary");
    });

    it("applies underline variant classes when variant is underline", () => {
        const wrapper = mount(AppTab, { props: { variant: "underline" } });
        expect(wrapper.find("button").classes()).toContain("border-b-2");
    });

    it("applies rose active classes when color is rose", () => {
        const wrapper = mount(AppTab, { props: { active: true, color: "rose" } });
        expect(wrapper.find("button").classes()).toContain("bg-rose-500/15");
        expect(wrapper.find("button").classes()).toContain("text-rose-400");
    });

    it("uses activeClass override when provided", () => {
        const wrapper = mount(AppTab, { props: { active: true, activeClass: "custom-active" } });
        expect(wrapper.find("button").classes()).toContain("custom-active");
    });

    it("uses inactiveClass override when inactive", () => {
        const wrapper = mount(AppTab, { props: { active: false, inactiveClass: "custom-inactive" } });
        expect(wrapper.find("button").classes()).toContain("custom-inactive");
    });
});
