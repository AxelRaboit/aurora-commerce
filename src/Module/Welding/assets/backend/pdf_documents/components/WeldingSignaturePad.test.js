/**
 * @vitest-environment happy-dom
 */
import { describe, expect, it, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import SignaturePad from "./WeldingSignaturePad.vue";

const messages = {
    backend: {
        welding: {
            pdf_documents: {
                signatureHint: "Dessinez votre signature ici…",
                signatureClear: "Effacer",
            },
        },
    },
};

function mountSignaturePad() {
    return mount(SignaturePad, {
        global: { plugins: [createTestI18n(messages)] },
        attachTo: document.body,
    });
}

beforeEach(() => {
    HTMLCanvasElement.prototype.getContext = () => ({
        scale: () => {},
        beginPath: () => {},
        moveTo: () => {},
        lineTo: () => {},
        stroke: () => {},
        clearRect: () => {},
    });
    HTMLCanvasElement.prototype.toDataURL = () => "data:image/png;base64,abc";
});

describe("SignaturePad", () => {
    it("renders a canvas element", () => {
        const wrapper = mountSignaturePad();

        expect(wrapper.find("canvas").exists()).toBe(true);
    });

    it("shows the hint text when no signature has been drawn", () => {
        const wrapper = mountSignaturePad();

        expect(wrapper.text()).toContain("Dessinez votre signature ici…");
    });

    it("renders the clear button", () => {
        const wrapper = mountSignaturePad();

        expect(wrapper.text()).toContain("Effacer");
    });

    it("clear button is disabled before any drawing", () => {
        const wrapper = mountSignaturePad();

        const button = wrapper.find("button");
        expect(button.attributes("disabled")).toBeDefined();
    });

    it("emits a data URL after mousedown+mousemove on the canvas", async () => {
        const wrapper = mountSignaturePad();
        const canvas = wrapper.find("canvas");

        await canvas.trigger("mousedown", { clientX: 10, clientY: 10 });
        await canvas.trigger("mousemove", { clientX: 20, clientY: 20 });
        await canvas.trigger("mouseup");

        const emitted = wrapper.emitted("update:modelValue");
        expect(emitted).toBeTruthy();
        expect(typeof emitted[0][0]).toBe("string");
    });

    it("emits null after drawing then clearing", async () => {
        const wrapper = mountSignaturePad();
        const canvas = wrapper.find("canvas");

        await canvas.trigger("mousedown", { clientX: 10, clientY: 10 });
        await canvas.trigger("mousemove", { clientX: 20, clientY: 20 });
        await canvas.trigger("mouseup");

        await wrapper.find("button").trigger("click");

        const emitted = wrapper.emitted("update:modelValue");
        expect(emitted.at(-1)).toEqual([null]);
    });
});
