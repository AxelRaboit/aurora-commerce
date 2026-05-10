/**
 * @vitest-environment happy-dom
 */
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent, h, nextTick } from "vue";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import { usePdfDocumentsForm } from "./usePdfDocumentsForm.js";

vi.mock("vue-sonner", () => ({
    toast: { success: vi.fn(), error: vi.fn() },
}));

vi.mock("../composables/usePdfLivePreview.js", () => ({
    usePdfLivePreview: () => ({
        fieldPositions: { value: {} },
        render: vi.fn(),
        reset: vi.fn(),
    }),
}));

const PICKER_RESPONSE = {
    success: true,
    items: [],
    page: 1,
    totalPages: 1,
};

const GENERATE_SUCCESS_RESPONSE = {
    success: true,
};

const pdfformMessages = {
    backend: {
        pdfform: {
            documents: {
                generate: "Générer le document",
                deleted: "Document supprimé.",
                errors: {
                    template_required: "Le template est obligatoire.",
                },
            },
        },
    },
};

function mountWithComposable(setupFn) {
    const Wrapper = defineComponent({
        setup: () => {
            setupFn();
            return () => h("div");
        },
    });
    return mount(Wrapper, {
        global: { plugins: [createTestI18n(pdfformMessages)] },
    });
}

function makeTemplate(overrides = {}) {
    return {
        id: 1,
        name: "Contrat",
        fileUrl: null,
        requiresSignature: false,
        fields: [],
        ...overrides,
    };
}

beforeEach(() => {
    vi.stubGlobal(
        "fetch",
        vi.fn().mockResolvedValue({
            ok: true,
            json: async () => PICKER_RESPONSE,
        }),
    );
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.clearAllMocks();
});

describe("usePdfDocumentsForm — step navigation", () => {
    it("step is 1 after openModal", async () => {
        let api;
        mountWithComposable(() => {
            api = usePdfDocumentsForm(
                "/generate",
                "/delete",
                "/templates",
                vi.fn(),
            );
        });

        api.openModal();
        await nextTick();

        expect(api.step.value).toBe(1);
    });

    it("selectTemplate moves to step 2 and sets editorTemplate", async () => {
        let api;
        mountWithComposable(() => {
            api = usePdfDocumentsForm(
                "/generate",
                "/delete",
                "/templates",
                vi.fn(),
            );
        });

        const template = makeTemplate({ name: "Mon contrat" });
        api.selectTemplate(template);
        await nextTick();

        expect(api.step.value).toBe(2);
        expect(api.editorTemplate.value.name).toBe("Mon contrat");
    });

    it("goToSignature moves to step 3", async () => {
        let api;
        mountWithComposable(() => {
            api = usePdfDocumentsForm(
                "/generate",
                "/delete",
                "/templates",
                vi.fn(),
            );
        });

        api.selectTemplate(makeTemplate());
        api.goToSignature();
        await nextTick();

        expect(api.step.value).toBe(3);
    });

    it("backToEditor returns to step 2 from step 3", async () => {
        let api;
        mountWithComposable(() => {
            api = usePdfDocumentsForm(
                "/generate",
                "/delete",
                "/templates",
                vi.fn(),
            );
        });

        api.selectTemplate(makeTemplate());
        api.goToSignature();
        api.backToEditor();
        await nextTick();

        expect(api.step.value).toBe(2);
    });

    it("backToPicker returns to step 1 from step 2", async () => {
        let api;
        mountWithComposable(() => {
            api = usePdfDocumentsForm(
                "/generate",
                "/delete",
                "/templates",
                vi.fn(),
            );
        });

        api.selectTemplate(makeTemplate());
        api.backToPicker();
        await nextTick();

        expect(api.step.value).toBe(1);
    });

    it("selectTemplate resets signatureData to null", async () => {
        let api;
        mountWithComposable(() => {
            api = usePdfDocumentsForm(
                "/generate",
                "/delete",
                "/templates",
                vi.fn(),
            );
        });

        api.selectTemplate(makeTemplate({ requiresSignature: true }));
        api.signatureData.value = "data:image/png;base64,abc";
        api.selectTemplate(makeTemplate());

        expect(api.signatureData.value).toBeNull();
    });
});

describe("usePdfDocumentsForm — submitGenerate", () => {
    it("includes __signature__ in payload when template requiresSignature and signatureData is set", async () => {
        let api;
        mountWithComposable(() => {
            api = usePdfDocumentsForm(
                "/generate",
                "/delete",
                "/templates",
                vi.fn(),
            );
        });

        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => PICKER_RESPONSE,
        });
        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => GENERATE_SUCCESS_RESPONSE,
        });

        const signatureDataUrl = "data:image/png;base64,iVBORw0KGgo=";
        api.selectTemplate(makeTemplate({ id: 1, requiresSignature: true }));
        api.generateForm.value.templateId = 1;
        api.signatureData.value = signatureDataUrl;

        await api.submitGenerate();
        await nextTick();

        const generateCall = fetch.mock.calls.find(
            (call) => call[0] === "/generate",
        );
        expect(generateCall).toBeDefined();

        const body = JSON.parse(generateCall[1].body);
        expect(body.fieldValues.__signature__).toBe(signatureDataUrl);
    });

    it("does not include __signature__ when template does not require signature", async () => {
        let api;
        mountWithComposable(() => {
            api = usePdfDocumentsForm(
                "/generate",
                "/delete",
                "/templates",
                vi.fn(),
            );
        });

        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => PICKER_RESPONSE,
        });
        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => GENERATE_SUCCESS_RESPONSE,
        });

        api.selectTemplate(makeTemplate({ id: 1, requiresSignature: false }));
        api.generateForm.value.templateId = 1;
        api.signatureData.value = "data:image/png;base64,xyz";

        await api.submitGenerate();
        await nextTick();

        const generateCall = fetch.mock.calls.find(
            (call) => call[0] === "/generate",
        );
        expect(generateCall).toBeDefined();

        const body = JSON.parse(generateCall[1].body);
        expect(body.fieldValues.__signature__).toBeUndefined();
    });
});
