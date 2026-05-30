import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import AppImagePickerField from "./AppImagePickerField.vue";

// Stub the document picker utility to avoid DOM-level modal interactions
vi.mock("@/shared/utils/documentPicker.js", () => ({
    openDocumentPicker: vi.fn().mockResolvedValue(null),
}));

const i18n = createTestI18n({}, "en");

const globalConfig = {
    plugins: [i18n],
    stubs: {
        AppImage: {
            template: '<img :src="src" />',
            props: ["src", "alt", "objectFit"],
        },
    },
};

describe("AppImagePickerField", () => {
    it("renders label when label prop is set", () => {
        const wrapper = mount(AppImagePickerField, {
            props: {
                label: "Cover image",
                modelValue: { id: null, url: null },
            },
            global: globalConfig,
        });
        expect(wrapper.find("p").text()).toBe("Cover image");
    });

    it("shows image when modelValue.url is set", () => {
        const wrapper = mount(AppImagePickerField, {
            props: {
                modelValue: { id: 1, url: "https://example.com/img.jpg" },
            },
            global: globalConfig,
        });
        expect(wrapper.find("img").exists()).toBe(true);
        expect(wrapper.find("img").attributes("src")).toBe(
            "https://example.com/img.jpg",
        );
    });

    it("shows at least two buttons when image url is set (change + remove)", () => {
        const wrapper = mount(AppImagePickerField, {
            props: {
                modelValue: { id: 1, url: "https://example.com/img.jpg" },
            },
            global: globalConfig,
        });
        const buttons = wrapper.findAll("button");
        expect(buttons.length).toBeGreaterThanOrEqual(2);
    });

    it("shows choose button (no image) when modelValue.url is null", () => {
        const wrapper = mount(AppImagePickerField, {
            props: { modelValue: { id: null, url: null } },
            global: globalConfig,
        });
        expect(wrapper.find("img").exists()).toBe(false);
        expect(wrapper.find("button").exists()).toBe(true);
    });

    it("emits update:modelValue with null values when remove is clicked", async () => {
        const wrapper = mount(AppImagePickerField, {
            props: {
                modelValue: { id: 1, url: "https://example.com/img.jpg" },
            },
            global: globalConfig,
        });
        // The ghost/remove button is the last button in the actions block
        const buttons = wrapper.findAll("button");
        const removeBtn = buttons[buttons.length - 1];
        await removeBtn.trigger("click");
        const emitted = wrapper.emitted("update:modelValue");
        expect(emitted).toBeTruthy();
        expect(emitted[0][0]).toEqual({ id: null, url: null });
    });
});
