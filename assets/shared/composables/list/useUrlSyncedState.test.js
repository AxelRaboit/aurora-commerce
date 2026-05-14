/**
 * @vitest-environment happy-dom
 */
import { describe, it, expect, vi, beforeEach } from "vitest";
import { defineComponent, h } from "vue";
import { mount } from "@vue/test-utils";
import { useUrlSyncedState } from "./useUrlSyncedState.js";

function mountWithComposable(options) {
    let api;
    const Comp = defineComponent({
        setup() {
            api = useUrlSyncedState(options);
            return () => h("div");
        },
    });
    const wrapper = mount(Comp);
    return { wrapper, api: () => api };
}

describe("useUrlSyncedState", () => {
    beforeEach(() => {
        history.replaceState(null, "", "/");
    });

    it("initializes with the given initial value", () => {
        const { api } = mountWithComposable({ initial: "tab-a" });
        expect(api().state.value).toBe("tab-a");
    });

    it("set() updates state", () => {
        const { api } = mountWithComposable({ initial: "tab-a" });
        api().set("tab-b");
        expect(api().state.value).toBe("tab-b");
    });

    it("set() pushes to history when serialize returns a value", () => {
        const pushSpy = vi.spyOn(history, "pushState");
        const { api } = mountWithComposable({
            initial: "tab-a",
            serialize: (value) => `/?tab=${value}`,
        });
        api().set("tab-b");
        expect(pushSpy).toHaveBeenCalledWith(
            { value: "tab-b" },
            "",
            "/?tab=tab-b",
        );
        pushSpy.mockRestore();
    });

    it("set() does not push to history when serialize returns null", () => {
        const pushSpy = vi.spyOn(history, "pushState");
        const { api } = mountWithComposable({
            initial: "tab-a",
            serialize: () => null,
        });
        api().set("tab-b");
        expect(pushSpy).not.toHaveBeenCalled();
        pushSpy.mockRestore();
    });

    it("set() does nothing when value is already current", () => {
        const onSync = vi.fn();
        const { api } = mountWithComposable({ initial: "tab-a", onSync });
        api().set("tab-a");
        expect(onSync).not.toHaveBeenCalled();
    });
});
