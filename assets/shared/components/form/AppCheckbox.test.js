import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppCheckbox from "./AppCheckbox.vue";

describe("AppCheckbox", () => {
    it("renders unchecked by default", () => {
        const wrapper = mount(AppCheckbox);
        const input = wrapper.find("input[type='checkbox']");
        expect(input.exists()).toBe(true);
        expect(input.element.checked).toBe(false);
    });

    it("renders checked when modelValue is true", () => {
        const wrapper = mount(AppCheckbox, { props: { modelValue: true } });
        const input = wrapper.find("input[type='checkbox']");
        expect(input.element.checked).toBe(true);
    });

    it("applies disabled state", () => {
        const wrapper = mount(AppCheckbox, { props: { disabled: true } });
        const input = wrapper.find("input[type='checkbox']");
        expect(input.element.disabled).toBe(true);
        expect(wrapper.find("label").classes()).toContain("opacity-50");
    });

    it("renders label from prop", () => {
        const wrapper = mount(AppCheckbox, { props: { label: "Accept terms" } });
        expect(wrapper.text()).toContain("Accept terms");
    });

    it("emits update:modelValue on change", async () => {
        const wrapper = mount(AppCheckbox, { props: { modelValue: false } });
        const input = wrapper.find("input[type='checkbox']");
        await input.trigger("change");
        expect(wrapper.emitted("update:modelValue")).toBeTruthy();
    });
});
