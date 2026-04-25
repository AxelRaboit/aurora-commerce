import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppFieldLabel from "./AppFieldLabel.vue";

describe("AppFieldLabel", () => {
    it("renders label text", () => {
        const wrapper = mount(AppFieldLabel, {
            props: { label: "My Label" },
        });
        expect(wrapper.text()).toContain("My Label");
    });

    it("shows red asterisk when required=true", () => {
        const wrapper = mount(AppFieldLabel, {
            props: { label: "Required Field", required: true },
        });
        const asterisk = wrapper.find("span.text-red-500");
        expect(asterisk.exists()).toBe(true);
        expect(asterisk.text()).toBe("*");
    });

    it("does not show asterisk when required=false", () => {
        const wrapper = mount(AppFieldLabel, {
            props: { label: "Optional Field", required: false },
        });
        expect(wrapper.find("span.text-red-500").exists()).toBe(false);
    });

    it("renders nothing when label is empty", () => {
        const wrapper = mount(AppFieldLabel, {
            props: { label: "" },
        });
        expect(wrapper.find("label").exists()).toBe(false);
    });
});
