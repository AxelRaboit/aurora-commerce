import { describe, it, expect, vi, beforeEach } from "vitest";
import { defineComponent, h } from "vitest";
import { defineComponent as vueDefineComponent, h as vueH } from "vue";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import { useServerErrors } from "./useServerErrors.js";

vi.mock("vue-sonner", () => ({
    toast: { error: vi.fn(), success: vi.fn() },
}));

import { toast } from "vue-sonner";

function mountWithComposable(locale = "fr") {
    let api;
    const Comp = vueDefineComponent({
        setup() {
            api = useServerErrors();
            return () => vueH("div");
        },
    });
    mount(Comp, { global: { plugins: [createTestI18n({}, locale)] } });
    return api;
}

describe("useServerErrors", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("handleResponse does nothing when data is null", () => {
        const { errors, handleResponse } = mountWithComposable();
        handleResponse(null);
        expect(errors.value).toEqual({});
        expect(toast.error).not.toHaveBeenCalled();
    });

    it("handleResponse calls onSuccess and clears errors on success", () => {
        const { errors, setErrors, handleResponse } = mountWithComposable();
        setErrors({ field: "some_error" });
        const onSuccess = vi.fn();
        handleResponse({ success: true, data: { id: 1 } }, onSuccess);
        expect(errors.value).toEqual({});
        expect(onSuccess).toHaveBeenCalledWith({
            success: true,
            data: { id: 1 },
        });
    });

    it("handleResponse sets field errors from data.errors", () => {
        const { errors, handleResponse } = mountWithComposable();
        handleResponse({
            success: false,
            errors: { name: "already_a_string" },
        });
        expect(errors.value).toHaveProperty("name");
    });

    it("handleErrors toasts generic error when errors object is empty", () => {
        const { handleErrors } = mountWithComposable();
        handleErrors({});
        expect(toast.error).toHaveBeenCalledOnce();
    });

    it("handleErrors toasts _global message when present", () => {
        const { handleErrors } = mountWithComposable();
        handleErrors({ _global: "shared.common.error" });
        expect(toast.error).toHaveBeenCalledOnce();
    });
});
