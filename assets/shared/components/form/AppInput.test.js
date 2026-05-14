import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import { createI18n } from "vue-i18n";
import AppInput from "./AppInput.vue";

const i18n = createI18n({ legacy: false, locale: "en", messages: {} });

describe("AppInput", () => {
    it("renders the input element", () => {
        const wrapper = mount(AppInput, { global: { plugins: [i18n] } });
        expect(wrapper.find("input").exists()).toBe(true);
    });

    it("reflects placeholder prop on the input", () => {
        const wrapper = mount(AppInput, {
            props: { placeholder: "Enter your name" },
            global: { plugins: [i18n] },
        });
        expect(wrapper.find("input").attributes("placeholder")).toBe(
            "Enter your name",
        );
    });

    it("renders label text", () => {
        const wrapper = mount(AppInput, {
            props: { label: "Full name" },
            global: { plugins: [i18n] },
        });
        expect(wrapper.text()).toContain("Full name");
    });

    it("applies error border class when error prop is set", () => {
        const wrapper = mount(AppInput, {
            props: { error: "This field is required" },
            global: { plugins: [i18n] },
        });
        expect(wrapper.find("input").classes()).toContain("border-red-500");
    });

    it("emits update:modelValue with input value", async () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: "" },
            global: { plugins: [i18n] },
        });
        const input = wrapper.find("input");
        await input.setValue("hello");
        expect(wrapper.emitted("update:modelValue")).toBeTruthy();
        expect(wrapper.emitted("update:modelValue")[0][0]).toBe("hello");
    });
});
