import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import AppLoadMore from "./AppLoadMore.vue";

vi.mock("vue-i18n", () => ({
    useI18n: () => ({ t: (key) => key }),
}));

const stubAppButton = {
    template:
        '<button :disabled="disabled || loading" @click="$emit(\'click\')"><slot /></button>',
    props: ["variant", "size", "loading", "disabled"],
    emits: ["click"],
};

describe("AppLoadMore", () => {
    it("renders nothing when hasMore is false", () => {
        const wrapper = mount(AppLoadMore, {
            props: { hasMore: false },
            global: { stubs: { AppButton: stubAppButton } },
        });
        expect(wrapper.find("button").exists()).toBe(false);
    });

    it("renders button when hasMore is true", () => {
        const wrapper = mount(AppLoadMore, {
            props: { hasMore: true },
            global: { stubs: { AppButton: stubAppButton } },
        });
        expect(wrapper.find("button").exists()).toBe(true);
    });

    it("shows custom label when provided", () => {
        const wrapper = mount(AppLoadMore, {
            props: { hasMore: true, label: "Show more" },
            global: { stubs: { AppButton: stubAppButton } },
        });
        expect(wrapper.find("button").text()).toBe("Show more");
    });

    it("falls back to i18n key when no label provided", () => {
        const wrapper = mount(AppLoadMore, {
            props: { hasMore: true },
            global: { stubs: { AppButton: stubAppButton } },
        });
        expect(wrapper.find("button").text()).toBe("shared.common.loadMore");
    });

    it("emits load event on click", async () => {
        const wrapper = mount(AppLoadMore, {
            props: { hasMore: true },
            global: { stubs: { AppButton: stubAppButton } },
        });
        await wrapper.find("button").trigger("click");
        expect(wrapper.emitted("load")).toBeTruthy();
    });
});
