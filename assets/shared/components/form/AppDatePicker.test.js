import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import { ref } from "vue";

// useTheme accesses localStorage + window.matchMedia at module level — stub it
vi.mock("@/shared/composables/useTheme.js", () => ({
    useTheme: () => ({ theme: ref("light"), toggle: vi.fn() }),
}));

import AppDatePicker from "./AppDatePicker.vue";

const i18n = createTestI18n({}, "en");

const globalConfig = {
    plugins: [i18n],
    stubs: { VueDatePicker: true },
};

describe("AppDatePicker", () => {
    it("renders label text", () => {
        const wrapper = mount(AppDatePicker, {
            props: { label: "Birth date", modelValue: "" },
            global: globalConfig,
        });
        expect(wrapper.text()).toContain("Birth date");
    });

    it("shows required asterisk when required=true", () => {
        const wrapper = mount(AppDatePicker, {
            props: { label: "Date", required: true, modelValue: "" },
            global: globalConfig,
        });
        expect(wrapper.find("span.text-red-500").exists()).toBe(true);
    });

    it("renders error message when error prop is set", () => {
        const wrapper = mount(AppDatePicker, {
            props: { modelValue: "", error: "Invalid date" },
            global: globalConfig,
        });
        expect(wrapper.find("p.text-red-500").text()).toBe("Invalid date");
    });

    it("does not render error paragraph when error is empty", () => {
        const wrapper = mount(AppDatePicker, {
            props: { modelValue: "" },
            global: globalConfig,
        });
        expect(wrapper.find("p.text-red-500").exists()).toBe(false);
    });

    it("renders the stubbed VueDatePicker component", () => {
        const wrapper = mount(AppDatePicker, {
            props: { modelValue: "" },
            global: globalConfig,
        });
        expect(wrapper.find("vue-date-picker-stub").exists()).toBe(true);
    });
});
