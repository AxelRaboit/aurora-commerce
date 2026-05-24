import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppSelectionCheck from "./AppSelectionCheck.vue";

describe("AppSelectionCheck", () => {
    it("renders inactive by default with translucent bg and hidden icon color", () => {
        const wrapper = mount(AppSelectionCheck);
        const span = wrapper.find("span");
        expect(span.classes()).toContain("bg-black/40");
        expect(span.classes()).toContain("text-white/0");
        expect(span.classes()).not.toContain("bg-accent-500");
    });

    it("uses accent fill when active", () => {
        const wrapper = mount(AppSelectionCheck, { props: { active: true } });
        const span = wrapper.find("span");
        expect(span.classes()).toContain("bg-accent-500");
        expect(span.classes()).toContain("text-white");
        expect(span.classes()).not.toContain("bg-black/40");
    });

    it("applies size xs", () => {
        const wrapper = mount(AppSelectionCheck, { props: { size: "xs" } });
        expect(wrapper.find("span").classes()).toContain("w-5");
    });

    it("applies size md", () => {
        const wrapper = mount(AppSelectionCheck, { props: { size: "md" } });
        expect(wrapper.find("span").classes()).toContain("w-7");
    });

    it("renders an svg check icon inside the span", () => {
        const wrapper = mount(AppSelectionCheck);
        expect(wrapper.find("span svg").exists()).toBe(true);
    });
});
