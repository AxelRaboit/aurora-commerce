import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppThumbnail from "./AppThumbnail.vue";

describe("AppThumbnail", () => {
    it("renders AppImage when src is provided", () => {
        const wrapper = mount(AppThumbnail, {
            props: { src: "https://example.com/thumb.jpg", alt: "Thumb" },
            global: {
                stubs: {
                    AppImage: { template: '<img data-testid="app-image" />' },
                },
            },
        });
        expect(wrapper.find("[data-testid='app-image']").exists()).toBe(true);
    });

    it("renders slot content when src is null", () => {
        const wrapper = mount(AppThumbnail, {
            props: { src: null },
            slots: {
                default: '<span data-testid="slot-content">placeholder</span>',
            },
            global: { stubs: { AppImage: true } },
        });
        expect(wrapper.find("[data-testid='slot-content']").exists()).toBe(
            true,
        );
        expect(wrapper.find("[data-testid='slot-content']").text()).toBe(
            "placeholder",
        );
    });

    it("applies w-10 h-10 by default (size=sm)", () => {
        const wrapper = mount(AppThumbnail, {
            global: { stubs: { AppImage: true } },
        });
        const div = wrapper.find("div");
        expect(div.classes()).toContain("w-10");
        expect(div.classes()).toContain("h-10");
    });

    it("applies w-12 h-12 when size=md", () => {
        const wrapper = mount(AppThumbnail, {
            props: { size: "md" },
            global: { stubs: { AppImage: true } },
        });
        const div = wrapper.find("div");
        expect(div.classes()).toContain("w-12");
        expect(div.classes()).toContain("h-12");
    });

    it("applies w-16 h-10 when size=landscape", () => {
        const wrapper = mount(AppThumbnail, {
            props: { size: "landscape" },
            global: { stubs: { AppImage: true } },
        });
        const div = wrapper.find("div");
        expect(div.classes()).toContain("w-16");
        expect(div.classes()).toContain("h-10");
    });
});
