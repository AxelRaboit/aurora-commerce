import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppFileInput from "./AppFileInput.vue";

describe("AppFileInput", () => {
    it("renders a hidden file input", () => {
        const wrapper = mount(AppFileInput);
        const input = wrapper.find("input[type='file']");
        expect(input.exists()).toBe(true);
        expect(input.classes()).toContain("hidden");
    });

    it("sets the accept attribute from prop", () => {
        const wrapper = mount(AppFileInput, { props: { accept: "image/*" } });
        expect(wrapper.find("input[type='file']").attributes("accept")).toBe("image/*");
    });

    it("sets the multiple attribute when multiple=true", () => {
        const wrapper = mount(AppFileInput, { props: { multiple: true } });
        expect(wrapper.find("input[type='file']").attributes("multiple")).toBeDefined();
    });

    it("does not set multiple attribute when multiple=false", () => {
        const wrapper = mount(AppFileInput, { props: { multiple: false } });
        expect(wrapper.find("input[type='file']").attributes("multiple")).toBeUndefined();
    });

    it("renders slot content", () => {
        const wrapper = mount(AppFileInput, {
            slots: { default: "<button id='pick-btn'>Pick file</button>" },
        });
        expect(wrapper.find("#pick-btn").exists()).toBe(true);
    });
});
