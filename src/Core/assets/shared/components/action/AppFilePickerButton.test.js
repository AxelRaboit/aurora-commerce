import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import AppFilePickerButton from "./AppFilePickerButton.vue";

describe("AppFilePickerButton", () => {
    it("renders a hidden file input and a visible button", () => {
        const wrapper = mount(AppFilePickerButton);
        const input = wrapper.find("input[type='file']");
        const button = wrapper.find("button");
        expect(input.exists()).toBe(true);
        expect(input.classes()).toContain("hidden");
        expect(button.exists()).toBe(true);
    });

    it("forwards accept and multiple props to the file input", () => {
        const wrapper = mount(AppFilePickerButton, {
            props: { accept: "image/*", multiple: true },
        });
        const input = wrapper.find("input[type='file']");
        expect(input.attributes("accept")).toBe("image/*");
        expect(input.attributes("multiple")).toBeDefined();
    });

    it("emits change and files when the input changes", async () => {
        const wrapper = mount(AppFilePickerButton);
        const input = wrapper.find("input[type='file']");
        const file = new File(["content"], "photo.jpg", { type: "image/jpeg" });
        Object.defineProperty(input.element, "files", {
            value: [file],
            writable: false,
        });
        await input.trigger("change");
        expect(wrapper.emitted("change")).toBeTruthy();
        expect(wrapper.emitted("files")).toBeTruthy();
        expect(wrapper.emitted("files")[0][0]).toEqual([file]);
    });

    it("exposes open() which triggers a click on the hidden input", async () => {
        const wrapper = mount(AppFilePickerButton);
        const input = wrapper.find("input[type='file']");
        const clickSpy = vi.spyOn(input.element, "click");
        wrapper.vm.open();
        expect(clickSpy).toHaveBeenCalledOnce();
    });

    it("exposes reset() as a callable method", () => {
        const wrapper = mount(AppFilePickerButton);
        expect(typeof wrapper.vm.reset).toBe("function");
        expect(() => wrapper.vm.reset()).not.toThrow();
    });
});
