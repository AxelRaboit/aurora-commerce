import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { nextTick } from "vue";
import AppTooltip from "./AppTooltip.vue";

function mountTooltip(props = {}, slots = {}) {
    return mount(AppTooltip, {
        props: { delay: 0, ...props },
        slots: { default: "<button>Trigger</button>", ...slots },
        global: { stubs: { Teleport: true } },
    });
}

describe("AppTooltip", () => {
    it("does not show tooltip initially", () => {
        const wrapper = mountTooltip({ title: "Hello" });
        expect(wrapper.find('[role="tooltip"]').exists()).toBe(false);
    });

    it("shows tooltip with title after mouseenter", async () => {
        vi.useFakeTimers();
        const wrapper = mountTooltip({ title: "Hello" });
        await wrapper.find("div.contents").trigger("mouseenter");
        vi.runAllTimers();
        await nextTick();
        expect(wrapper.find('[role="tooltip"]').exists()).toBe(true);
        expect(wrapper.find('[role="tooltip"]').text()).toContain("Hello");
        vi.useRealTimers();
    });

    it("hides tooltip after mouseleave", async () => {
        vi.useFakeTimers();
        const wrapper = mountTooltip({ title: "Hello" });
        await wrapper.find("div.contents").trigger("mouseenter");
        vi.runAllTimers();
        await nextTick();
        await wrapper.find("div.contents").trigger("mouseleave");
        await nextTick();
        expect(wrapper.find('[role="tooltip"]').exists()).toBe(false);
        vi.useRealTimers();
    });

    it("does not show tooltip when disabled", async () => {
        vi.useFakeTimers();
        const wrapper = mountTooltip({ title: "Hello", disabled: true });
        await wrapper.find("div.contents").trigger("mouseenter");
        vi.runAllTimers();
        await nextTick();
        expect(wrapper.find('[role="tooltip"]').exists()).toBe(false);
        vi.useRealTimers();
    });

    it("does not show tooltip when title and description are empty", async () => {
        vi.useFakeTimers();
        const wrapper = mountTooltip({ title: "", description: "" });
        await wrapper.find("div.contents").trigger("mouseenter");
        vi.runAllTimers();
        await nextTick();
        expect(wrapper.find('[role="tooltip"]').exists()).toBe(false);
        vi.useRealTimers();
    });

    it("renders description when provided", async () => {
        vi.useFakeTimers();
        const wrapper = mountTooltip({ title: "Title", description: "Details here" });
        await wrapper.find("div.contents").trigger("mouseenter");
        vi.runAllTimers();
        await nextTick();
        expect(wrapper.find('[role="tooltip"]').text()).toContain("Details here");
        vi.useRealTimers();
    });
});
