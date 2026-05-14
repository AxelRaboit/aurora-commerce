import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppNavLink from "./AppNavLink.vue";

const stubAppTooltip = {
    template: "<div><slot /></div>",
    props: ["title", "description", "placement"],
};

describe("AppNavLink", () => {
    it("renders an anchor with the correct href", () => {
        const wrapper = mount(AppNavLink, {
            props: { href: "/dashboard" },
            global: { stubs: { AppTooltip: stubAppTooltip } },
        });
        expect(wrapper.find("a").attributes("href")).toBe("/dashboard");
    });

    it("applies active accent classes when active is true", () => {
        const wrapper = mount(AppNavLink, {
            props: { href: "/dashboard", active: true },
            global: { stubs: { AppTooltip: stubAppTooltip } },
        });
        expect(wrapper.find("a").classes()).toContain("bg-accent-600/15");
        expect(wrapper.find("a").classes()).toContain("text-accent-400");
    });

    it("applies hover classes when inactive", () => {
        const wrapper = mount(AppNavLink, {
            props: { href: "/dashboard", active: false },
            global: { stubs: { AppTooltip: stubAppTooltip } },
        });
        expect(wrapper.find("a").classes()).toContain("text-secondary");
    });

    it("sets data-sidebar-active when sidebarActive is true", () => {
        const wrapper = mount(AppNavLink, {
            props: { href: "/dashboard", sidebarActive: true },
            global: { stubs: { AppTooltip: stubAppTooltip } },
        });
        expect(wrapper.find("a").attributes("data-sidebar-active")).toBe(
            "true",
        );
    });

    it("omits data-sidebar-active attribute when sidebarActive is false", () => {
        const wrapper = mount(AppNavLink, {
            props: { href: "/dashboard", sidebarActive: false },
            global: { stubs: { AppTooltip: stubAppTooltip } },
        });
        expect(
            wrapper.find("a").attributes("data-sidebar-active"),
        ).toBeUndefined();
    });

    it("sets rel=noopener when target is _blank", () => {
        const wrapper = mount(AppNavLink, {
            props: { href: "/dashboard", target: "_blank" },
            global: { stubs: { AppTooltip: stubAppTooltip } },
        });
        expect(wrapper.find("a").attributes("rel")).toBe("noopener");
    });
});
