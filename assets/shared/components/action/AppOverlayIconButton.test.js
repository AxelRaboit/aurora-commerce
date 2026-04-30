import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppOverlayIconButton from "./AppOverlayIconButton.vue";

describe("AppOverlayIconButton", () => {
    it("renders a button with type=button", () => {
        const wrapper = mount(AppOverlayIconButton);
        expect(wrapper.find("button").attributes("type")).toBe("button");
    });

    it("renders default slot content", () => {
        const wrapper = mount(AppOverlayIconButton, {
            slots: { default: '<span data-test="icon">x</span>' },
        });
        expect(wrapper.find('[data-test="icon"]').exists()).toBe(true);
    });

    it("applies default variant classes when not active", () => {
        const wrapper = mount(AppOverlayIconButton);
        const cls = wrapper.find("button").classes();
        expect(cls).toContain("bg-black/40");
        expect(cls).toContain("w-10");
    });

    it("applies size lg", () => {
        const wrapper = mount(AppOverlayIconButton, { props: { size: "lg" } });
        expect(wrapper.find("button").classes()).toContain("w-12");
    });

    it("applies light variant", () => {
        const wrapper = mount(AppOverlayIconButton, {
            props: { variant: "light" },
        });
        const cls = wrapper.find("button").classes();
        expect(cls).toContain("bg-white/10");
    });

    it("applies danger variant hover style", () => {
        const wrapper = mount(AppOverlayIconButton, {
            props: { variant: "danger" },
        });
        expect(wrapper.find("button").classes()).toContain("hover:bg-red-600");
    });

    it("active flag forces accent color and ignores variant background", () => {
        const wrapper = mount(AppOverlayIconButton, {
            props: { active: true, variant: "light" },
        });
        const cls = wrapper.find("button").classes();
        expect(cls).toContain("text-accent-500");
        expect(cls).not.toContain("bg-white/10");
    });

    it("falls back to title for aria-label when ariaLabel not provided", () => {
        const wrapper = mount(AppOverlayIconButton, {
            props: { title: "Pick" },
        });
        expect(wrapper.find("button").attributes("aria-label")).toBe("Pick");
    });

    it("uses ariaLabel when explicitly set", () => {
        const wrapper = mount(AppOverlayIconButton, {
            props: { title: "T", ariaLabel: "Explicit" },
        });
        expect(wrapper.find("button").attributes("aria-label")).toBe(
            "Explicit",
        );
    });

    it("emits click", async () => {
        const wrapper = mount(AppOverlayIconButton);
        await wrapper.find("button").trigger("click");
        expect(wrapper.emitted("click")).toBeTruthy();
    });
});
