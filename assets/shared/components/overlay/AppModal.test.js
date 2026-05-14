import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createI18n } from "vue-i18n";
import AppModal from "./AppModal.vue";

vi.mock("@/shared/composables/overlay/useBackButtonClose.js", () => ({
    useBackButtonClose: () => ({ requestClose: vi.fn() }),
}));

const i18n = createI18n({
    legacy: false,
    locale: "en",
    messages: { en: { shared: { common: { close: "Close" } } } },
});

function mountModal(props = {}, slots = {}) {
    return mount(AppModal, {
        props,
        slots,
        global: { plugins: [i18n], stubs: { Teleport: true } },
    });
}

describe("AppModal", () => {
    it("does not render dialog when show is false", () => {
        const wrapper = mountModal({ show: false });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });

    it("renders dialog when show is true", () => {
        const wrapper = mountModal({ show: true });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
    });

    it("renders the title when provided", () => {
        const wrapper = mountModal({ show: true, title: "My Title" });
        expect(wrapper.find("h2").text()).toBe("My Title");
    });

    it("shows close button when closeable=true and title is set", () => {
        const wrapper = mountModal({ show: true, title: "T", closeable: true });
        expect(wrapper.find("button[aria-label]").exists()).toBe(true);
    });

    it("hides close button when closeable=false", () => {
        const wrapper = mountModal({
            show: true,
            title: "T",
            closeable: false,
        });
        expect(wrapper.find("button[aria-label]").exists()).toBe(false);
    });

    it("renders footer slot when provided", () => {
        const wrapper = mountModal(
            { show: true },
            { footer: "<button>Save</button>" },
        );
        expect(wrapper.html()).toContain("Save");
    });
});
