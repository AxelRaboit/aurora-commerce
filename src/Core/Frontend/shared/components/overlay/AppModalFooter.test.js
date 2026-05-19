import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppModalFooter from "./AppModalFooter.vue";

describe("AppModalFooter", () => {
    it("renders slot content", () => {
        const wrapper = mount(AppModalFooter, {
            slots: { default: "<button>Cancel</button><button>Save</button>" },
        });
        expect(wrapper.text()).toContain("Cancel");
        expect(wrapper.text()).toContain("Save");
    });

    it("does not add border classes by default", () => {
        const wrapper = mount(AppModalFooter);
        const div = wrapper.find("div");
        expect(div.classes()).not.toContain("border-t");
    });

    it("adds border classes when bordered prop is true", () => {
        const wrapper = mount(AppModalFooter, { props: { bordered: true } });
        const div = wrapper.find("div");
        expect(div.classes()).toContain("border-t");
        expect(div.classes()).toContain("pt-2");
    });

    it("renders as a flex container", () => {
        const wrapper = mount(AppModalFooter);
        expect(wrapper.find("div").classes()).toContain("flex");
    });

    it("aligns items to the end on sm+ screens", () => {
        const wrapper = mount(AppModalFooter);
        expect(wrapper.find("div").classes()).toContain("sm:justify-end");
    });
});
