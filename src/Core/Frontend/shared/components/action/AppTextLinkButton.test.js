import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppTextLinkButton from "./AppTextLinkButton.vue";

describe("AppTextLinkButton", () => {
    it("renders slot content in a button", () => {
        const wrapper = mount(AppTextLinkButton, {
            slots: { default: "Clear" },
        });
        expect(wrapper.find("button").text()).toBe("Clear");
        expect(wrapper.find("button").attributes("type")).toBe("button");
    });

    it("default color uses accent text", () => {
        const wrapper = mount(AppTextLinkButton);
        expect(wrapper.find("button").classes()).toContain("text-accent-500");
    });

    it("danger color uses danger text", () => {
        const wrapper = mount(AppTextLinkButton, {
            props: { color: "danger" },
        });
        expect(wrapper.find("button").classes()).toContain("text-danger");
    });

    it("muted color uses muted text", () => {
        const wrapper = mount(AppTextLinkButton, { props: { color: "muted" } });
        expect(wrapper.find("button").classes()).toContain("text-muted");
    });

    it("size xs maps to text-xs", () => {
        const wrapper = mount(AppTextLinkButton, { props: { size: "xs" } });
        expect(wrapper.find("button").classes()).toContain("text-xs");
    });

    it("size md maps to text-base", () => {
        const wrapper = mount(AppTextLinkButton, { props: { size: "md" } });
        expect(wrapper.find("button").classes()).toContain("text-base");
    });

    it("emits click", async () => {
        const wrapper = mount(AppTextLinkButton);
        await wrapper.find("button").trigger("click");
        expect(wrapper.emitted("click")).toBeTruthy();
    });
});
