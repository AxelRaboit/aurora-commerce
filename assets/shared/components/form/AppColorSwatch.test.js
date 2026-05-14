import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppColorSwatch from "./AppColorSwatch.vue";

describe("AppColorSwatch", () => {
    it("renders an input of type color", () => {
        const wrapper = mount(AppColorSwatch, { props: { modelValue: "#ff0000" } });
        const input = wrapper.find("input[type='color']");
        expect(input.exists()).toBe(true);
    });

    it("reflects modelValue as value attribute", () => {
        const wrapper = mount(AppColorSwatch, { props: { modelValue: "#3b82f6" } });
        expect(wrapper.find("input[type='color']").element.value).toBe("#3b82f6");
    });

    it("applies disabled attribute when disabled prop is true", () => {
        const wrapper = mount(AppColorSwatch, {
            props: { modelValue: "#000000", disabled: true },
        });
        expect(wrapper.find("input[type='color']").element.disabled).toBe(true);
    });

    it("applies sm size class when size is sm", () => {
        const wrapper = mount(AppColorSwatch, {
            props: { modelValue: "#000000", size: "sm" },
        });
        expect(wrapper.find("input[type='color']").classes()).toContain("w-8");
    });

    it("emits update:modelValue on input", async () => {
        const wrapper = mount(AppColorSwatch, { props: { modelValue: "#000000" } });
        await wrapper.find("input[type='color']").trigger("input");
        expect(wrapper.emitted("update:modelValue")).toBeTruthy();
    });
});
