import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppListItemButton from "./AppListItemButton.vue";

describe("AppListItemButton", () => {
    it("renders a button with type=button", () => {
        const wrapper = mount(AppListItemButton);
        expect(wrapper.find("button").attributes("type")).toBe("button");
    });

    it("renders default slot content", () => {
        const wrapper = mount(AppListItemButton, {
            slots: { default: "Pick me" },
        });
        expect(wrapper.text()).toContain("Pick me");
    });

    it("renders icon slot when provided", () => {
        const wrapper = mount(AppListItemButton, {
            slots: {
                icon: '<span data-test="ico">i</span>',
                default: "Label",
            },
        });
        expect(wrapper.find('[data-test="ico"]').exists()).toBe(true);
    });

    it("renders meta slot in a sub-line", () => {
        const wrapper = mount(AppListItemButton, {
            slots: { default: "Title", meta: "Subtitle" },
        });
        expect(wrapper.find("span.text-muted").exists()).toBe(true);
        expect(wrapper.find("span.text-muted").text()).toBe("Subtitle");
    });

    it("omits the meta sub-line when slot is empty", () => {
        const wrapper = mount(AppListItemButton, {
            slots: { default: "Title" },
        });
        expect(wrapper.find("span.text-muted").exists()).toBe(false);
    });

    it("applies inactive classes by default", () => {
        const wrapper = mount(AppListItemButton);
        const cls = wrapper.find("button").classes();
        expect(cls).toContain("hover:bg-surface-2");
        expect(cls).not.toContain("bg-accent-600/15");
    });

    it("applies active classes when active=true", () => {
        const wrapper = mount(AppListItemButton, { props: { active: true } });
        const cls = wrapper.find("button").classes();
        expect(cls).toContain("bg-accent-600/15");
        expect(cls).toContain("text-accent-400");
    });

    it("emits click", async () => {
        const wrapper = mount(AppListItemButton);
        await wrapper.find("button").trigger("click");
        expect(wrapper.emitted("click")).toBeTruthy();
    });
});
