import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppLogo from "./AppLogo.vue";

describe("AppLogo", () => {
    it("renders an SVG element", () => {
        const wrapper = mount(AppLogo);
        expect(wrapper.find("svg").exists()).toBe(true);
    });

    it("uses default size of 40", () => {
        const wrapper = mount(AppLogo);
        const svg = wrapper.find("svg");
        expect(svg.attributes("width")).toBe("40");
        expect(svg.attributes("height")).toBe("40");
    });

    it("applies custom size prop to width and height", () => {
        const wrapper = mount(AppLogo, { props: { size: 64 } });
        const svg = wrapper.find("svg");
        expect(svg.attributes("width")).toBe("64");
        expect(svg.attributes("height")).toBe("64");
    });

    it("contains the letter V as the logo mark", () => {
        const wrapper = mount(AppLogo);
        expect(wrapper.find("text").text()).toBe("V");
    });

    it("renders a linearGradient with a unique id", () => {
        const wrapper = mount(AppLogo);
        const gradient = wrapper.find("linearGradient");
        expect(gradient.exists()).toBe(true);
        expect(gradient.attributes("id")).toMatch(/^aurora-bg-\d+$/);
    });
});
