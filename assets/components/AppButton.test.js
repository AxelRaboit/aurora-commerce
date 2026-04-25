import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppButton from "./AppButton.vue";

describe("AppButton", () => {
    it("renders slot content", () => {
        const wrapper = mount(AppButton, {
            slots: { default: "Click me" },
        });
        expect(wrapper.text()).toContain("Click me");
    });

    it("applies primary variant classes", () => {
        const wrapper = mount(AppButton, {
            props: { variant: "primary" },
        });
        expect(wrapper.find("button").classes()).toContain("bg-indigo-600");
    });

    it("applies ghost variant classes", () => {
        const wrapper = mount(AppButton, {
            props: { variant: "ghost" },
        });
        expect(wrapper.find("button").classes()).toContain("bg-transparent");
        expect(wrapper.find("button").classes()).toContain("text-secondary");
    });

    it("shows loading spinner when loading=true", () => {
        const wrapper = mount(AppButton, {
            props: { loading: true },
        });
        // Loader2 renders an SVG with animate-spin class
        expect(wrapper.find("svg.animate-spin").exists()).toBe(true);
    });

    it("is disabled when loading=true", () => {
        const wrapper = mount(AppButton, {
            props: { loading: true },
        });
        expect(wrapper.find("button").attributes("disabled")).toBeDefined();
    });
});
