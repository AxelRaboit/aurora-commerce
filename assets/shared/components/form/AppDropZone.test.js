import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import { createI18n } from "vue-i18n";
import AppDropZone from "./AppDropZone.vue";

const i18n = createI18n({ legacy: false, locale: "en", messages: {} });

describe("AppDropZone", () => {
    it("renders the drop zone container", () => {
        const wrapper = mount(AppDropZone, {
            props: {},
            global: { plugins: [i18n] },
        });
        expect(wrapper.find(".border-dashed").exists()).toBe(true);
    });

    it("displays label text from prop", () => {
        const wrapper = mount(AppDropZone, {
            props: { label: "Drop files here" },
            global: { plugins: [i18n] },
        });
        expect(wrapper.text()).toContain("Drop files here");
    });

    it("displays hint text when hint prop is set", () => {
        const wrapper = mount(AppDropZone, {
            props: { hint: "JPG, PNG only" },
            global: { plugins: [i18n] },
        });
        expect(wrapper.find("p.text-xs.text-muted").text()).toBe("JPG, PNG only");
    });

    it("renders upload icon", () => {
        const wrapper = mount(AppDropZone, {
            props: {},
            global: { plugins: [i18n] },
        });
        // Upload icon (lucide) renders as an svg
        expect(wrapper.find("svg").exists()).toBe(true);
    });

    it("contains a hidden file input via AppFileInput", () => {
        const wrapper = mount(AppDropZone, {
            props: {},
            global: { plugins: [i18n] },
        });
        expect(wrapper.find("input[type='file']").exists()).toBe(true);
    });
});
