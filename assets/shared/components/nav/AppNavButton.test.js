import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppNavButton from "./AppNavButton.vue";

const stubAppTooltip = {
    template: '<div><slot /></div>',
    props: ["title", "description", "placement"],
};

describe("AppNavButton", () => {
    it("renders a button element", () => {
        const wrapper = mount(AppNavButton, {
            global: { stubs: { AppTooltip: stubAppTooltip } },
        });
        expect(wrapper.find("button").exists()).toBe(true);
    });

    it("defaults to type='button'", () => {
        const wrapper = mount(AppNavButton, {
            global: { stubs: { AppTooltip: stubAppTooltip } },
        });
        expect(wrapper.find("button").attributes("type")).toBe("button");
    });

    it("applies primary hover classes by default", () => {
        const wrapper = mount(AppNavButton, {
            global: { stubs: { AppTooltip: stubAppTooltip } },
        });
        expect(wrapper.find("button").classes()).toContain("text-secondary");
        expect(wrapper.find("button").classes()).toContain("hover:text-primary");
    });

    it("applies rose hover classes when hoverColor is rose", () => {
        const wrapper = mount(AppNavButton, {
            props: { hoverColor: "rose" },
            global: { stubs: { AppTooltip: stubAppTooltip } },
        });
        expect(wrapper.find("button").classes()).toContain("hover:text-rose-400");
    });

    it("renders slot content", () => {
        const wrapper = mount(AppNavButton, {
            slots: { default: "<span>Action</span>" },
            global: { stubs: { AppTooltip: stubAppTooltip } },
        });
        expect(wrapper.find("span").text()).toBe("Action");
    });
});
