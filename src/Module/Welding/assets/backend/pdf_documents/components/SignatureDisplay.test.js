/**
 * @vitest-environment happy-dom
 */
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import SignatureDisplay from "./SignatureDisplay.vue";

const pdfformMessages = {
    backend: {
        pdfform: {
            documents: {
                signatureTitle: "Signature",
            },
        },
    },
};

function mountSignatureDisplay(src) {
    return mount(SignatureDisplay, {
        props: { src },
        global: { plugins: [createTestI18n(pdfformMessages)] },
    });
}

describe("SignatureDisplay", () => {
    it("renders an img element with the provided src", () => {
        const signatureDataUrl = "data:image/png;base64,iVBORw0KGgo=";
        const wrapper = mountSignatureDisplay(signatureDataUrl);

        const img = wrapper.find("img");
        expect(img.exists()).toBe(true);
        expect(img.attributes("src")).toBe(signatureDataUrl);
    });

    it("sets the alt attribute to the signature title translation", () => {
        const wrapper = mountSignatureDisplay("data:image/png;base64,abc");

        expect(wrapper.find("img").attributes("alt")).toBe("Signature");
    });

    it("displays the signature title label", () => {
        const wrapper = mountSignatureDisplay("data:image/png;base64,abc");

        expect(wrapper.text()).toContain("Signature");
    });
});
