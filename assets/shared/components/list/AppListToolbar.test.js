import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppListToolbar from "./AppListToolbar.vue";

describe("AppListToolbar", () => {
    it("renders default slot", () => {
        const wrapper = mount(AppListToolbar, {
            slots: { default: '<input data-testid="search" />' },
        });
        expect(wrapper.find('[data-testid="search"]').exists()).toBe(true);
    });

    it("renders actions slot", () => {
        const wrapper = mount(AppListToolbar, {
            slots: { actions: '<button data-testid="action">Add</button>' },
        });
        expect(wrapper.find('[data-testid="action"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="action"]').text()).toBe("Add");
    });

    it("renders default and actions slots side by side", () => {
        const wrapper = mount(AppListToolbar, {
            slots: {
                default: '<input data-testid="search" />',
                actions: '<button data-testid="action">Add</button>',
            },
        });
        const root = wrapper.find("div");
        expect(root.classes()).toContain("grid");
        expect(root.classes()).toContain("grid-cols-1");
        expect(root.classes()).toContain("sm:grid-cols-[1fr_auto]");
        expect(wrapper.find('[data-testid="search"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="action"]').exists()).toBe(true);
    });
});
