import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import AppPasswordStrength from "./AppPasswordStrength.vue";

const i18n = createTestI18n({}, "en");

describe("AppPasswordStrength", () => {
    it("renders 4 criteria items", () => {
        const wrapper = mount(AppPasswordStrength, {
            props: { password: "" },
            global: { plugins: [i18n] },
        });
        expect(wrapper.findAll("li").length).toBe(4);
    });

    it("no criterion is met when password is empty", () => {
        const wrapper = mount(AppPasswordStrength, {
            props: { password: "" },
            global: { plugins: [i18n] },
        });
        const metItems = wrapper.findAll("li.text-emerald-500");
        expect(metItems.length).toBe(0);
    });

    it("marks length criterion as met when password has 8+ chars", () => {
        const wrapper = mount(AppPasswordStrength, {
            props: { password: "abcdefgh" },
            global: { plugins: [i18n] },
        });
        // First li corresponds to 'length' rule
        const firstLi = wrapper.findAll("li")[0];
        expect(firstLi.classes()).toContain("text-emerald-500");
    });

    it("all criteria met for a strong password", () => {
        const wrapper = mount(AppPasswordStrength, {
            props: { password: "Str0ng!Pass" },
            global: { plugins: [i18n] },
        });
        const metItems = wrapper.findAll("li.text-emerald-500");
        expect(metItems.length).toBe(4);
    });

    it("renders Check icon for met criteria", () => {
        const wrapper = mount(AppPasswordStrength, {
            props: { password: "Str0ng!Pass" },
            global: { plugins: [i18n] },
        });
        // Lucide Check renders as svg
        expect(wrapper.findAll("svg").length).toBeGreaterThan(0);
    });
});
