/**
 * @vitest-environment happy-dom
 */
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent, h, nextTick, ref } from "vue";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";

vi.mock("vue-sonner", () => ({
    toast: { success: vi.fn(), error: vi.fn() },
}));

import { toast } from "vue-sonner";
import { useOrderRefund } from "@ecommerce/backend/orders/composables/useOrderRefund.js";

const toastMock = toast;

function mountWithComposable(setupFn) {
    let api;
    const Comp = defineComponent({
        setup: () => {
            api = setupFn();
            return () => h("div");
        },
    });
    mount(Comp, { global: { plugins: [createTestI18n()] } });
    return api;
}

beforeEach(() => {
    toastMock.success.mockClear();
    toastMock.error.mockClear();
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe("useOrderRefund", () => {
    it("open() resets state and shows the modal", () => {
        const order = ref({ id: 1, status: "paid" });
        const api = mountWithComposable(() =>
            useOrderRefund("/backend/orders/1/refund", order),
        );
        api.refundAmount.value = "10";
        api.isFullRefund.value = false;
        api.showModal.value = false;

        api.open();

        expect(api.showModal.value).toBe(true);
        expect(api.refundAmount.value).toBe("");
        expect(api.isFullRefund.value).toBe(true);
    });

    it("close() hides the modal", () => {
        const order = ref({});
        const api = mountWithComposable(() => useOrderRefund("/x", order));
        api.showModal.value = true;
        api.close();
        expect(api.showModal.value).toBe(false);
    });

    it("confirm() sends empty body for a full refund and merges the response order", async () => {
        vi.stubGlobal(
            "fetch",
            vi.fn().mockResolvedValue({
                ok: true,
                status: 200,
                json: async () => ({
                    success: true,
                    order: { status: "refunded", refundedCents: 5000 },
                }),
            }),
        );

        const order = ref({ id: 7, status: "paid", number: "ORD-7" });
        const api = mountWithComposable(() =>
            useOrderRefund("/backend/orders/7/refund", order),
        );

        api.open();
        await api.confirm();

        const [url, options] = fetch.mock.calls[0];
        expect(url).toBe("/backend/orders/7/refund");
        expect(JSON.parse(options.body)).toEqual({});

        expect(order.value).toMatchObject({
            id: 7,
            number: "ORD-7",
            status: "refunded",
            refundedCents: 5000,
        });
        expect(api.showModal.value).toBe(false);
        expect(toastMock.success).toHaveBeenCalledOnce();
    });

    it("confirm() converts euros to cents on partial refund", async () => {
        vi.stubGlobal(
            "fetch",
            vi.fn().mockResolvedValue({
                ok: true,
                status: 200,
                json: async () => ({ success: true, order: {} }),
            }),
        );

        const order = ref({});
        const api = mountWithComposable(() => useOrderRefund("/refund", order));

        api.open();
        api.isFullRefund.value = false;
        api.refundAmount.value = "12.34";

        await api.confirm();

        expect(JSON.parse(fetch.mock.calls[0][1].body)).toEqual({
            amountCents: 1234,
        });
    });

    it("confirm() shows error toast when API returns an error key", async () => {
        vi.stubGlobal(
            "fetch",
            vi.fn().mockResolvedValue({
                ok: true,
                status: 200,
                json: async () => ({
                    error: "backend.ecommerce.errors.refund_failed",
                }),
            }),
        );

        const order = ref({});
        const api = mountWithComposable(() => useOrderRefund("/refund", order));

        api.open();
        await api.confirm();

        expect(toastMock.success).not.toHaveBeenCalled();
        expect(toastMock.error).toHaveBeenCalledOnce();
        expect(api.showModal.value).toBe(true);
    });
});
