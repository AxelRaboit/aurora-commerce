import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import AppSearchInput from "./AppSearchInput.vue";

const i18n = createTestI18n({}, "en");

describe("AppSearchInput", () => {
    it("renders the search input", () => {
        const wrapper = mount(AppSearchInput, {
            props: { modelValue: "" },
            global: { plugins: [i18n] },
        });
        expect(wrapper.find("input").exists()).toBe(true);
    });

    it("reflects placeholder prop", () => {
        const wrapper = mount(AppSearchInput, {
            props: { modelValue: "", placeholder: "Search…" },
            global: { plugins: [i18n] },
        });
        expect(wrapper.find("input").attributes("placeholder")).toBe("Search…");
    });

    it("renders the search icon (svg)", () => {
        const wrapper = mount(AppSearchInput, {
            props: { modelValue: "" },
            global: { plugins: [i18n] },
        });
        expect(wrapper.find("svg").exists()).toBe(true);
    });

    it("shows clear button when value is non-empty and clearable=true", () => {
        const wrapper = mount(AppSearchInput, {
            props: { modelValue: "hello", clearable: true },
            global: { plugins: [i18n] },
        });
        expect(wrapper.find("button").exists()).toBe(true);
    });

    it("hides clear button when value is empty", () => {
        const wrapper = mount(AppSearchInput, {
            props: { modelValue: "", clearable: true },
            global: { plugins: [i18n] },
        });
        expect(wrapper.find("button").exists()).toBe(false);
    });
});
