/**
 * @vitest-environment happy-dom
 */
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { defineComponent, h } from "vue";
import { mount } from "@vue/test-utils";
import { useDebounce } from "./useDebounce.js";

function mountWithDebounce(callback, delay) {
    let debounced;
    const Comp = defineComponent({
        setup() {
            debounced = useDebounce(callback, delay);
            return () => h("div");
        },
    });
    const wrapper = mount(Comp);
    return { wrapper, fn: () => debounced };
}

describe("useDebounce", () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it("does not call the callback immediately", () => {
        const callback = vi.fn();
        const { fn } = mountWithDebounce(callback, 300);
        fn()("hello");
        expect(callback).not.toHaveBeenCalled();
    });

    it("calls the callback after the delay", () => {
        const callback = vi.fn();
        const { fn } = mountWithDebounce(callback, 300);
        fn()("hello");
        vi.runAllTimers();
        expect(callback).toHaveBeenCalledOnce();
        expect(callback).toHaveBeenCalledWith("hello");
    });

    it("only fires once when called multiple times within the delay", () => {
        const callback = vi.fn();
        const { fn } = mountWithDebounce(callback, 300);
        fn()("a");
        fn()("b");
        fn()("c");
        vi.runAllTimers();
        expect(callback).toHaveBeenCalledOnce();
        expect(callback).toHaveBeenCalledWith("c");
    });

    it("fires again for a second burst after the first settles", () => {
        const callback = vi.fn();
        const { fn } = mountWithDebounce(callback, 300);
        fn()("first");
        vi.runAllTimers();
        fn()("second");
        vi.runAllTimers();
        expect(callback).toHaveBeenCalledTimes(2);
        expect(callback).toHaveBeenLastCalledWith("second");
    });

    it("clears the timer on component unmount", () => {
        const callback = vi.fn();
        const { fn, wrapper } = mountWithDebounce(callback, 300);
        fn()("will-be-cancelled");
        wrapper.unmount();
        vi.runAllTimers();
        expect(callback).not.toHaveBeenCalled();
    });
});
