/**
 * @vitest-environment happy-dom
 */
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent, h, nextTick } from "vue";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import { useProjectsListPage } from "@project/backend/projects/composables/useProjectsListPage.js";

const PAYLOAD = {
    success: true,
    items: [{ id: 1, title: "Project A" }],
    total: 1,
    page: 1,
    totalPages: 1,
    status: null,
};

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
    history.replaceState(null, "", "/?other=keep");
    vi.stubGlobal(
        "fetch",
        vi.fn().mockResolvedValue({ ok: true, json: async () => PAYLOAD }),
    );
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe("useProjectsListPage", () => {
    it("setStatusFilter updates ref and triggers a reload with status param", async () => {
        const props = { listPath: "/backend/projects/list" };
        const api = mountWithComposable(() => useProjectsListPage(props));
        await nextTick();
        await nextTick();

        // Initial call (mount)
        const initialCalls = fetch.mock.calls.length;

        api.setStatusFilter("active");
        await nextTick();
        await nextTick();

        expect(api.statusFilter.value).toBe("active");
        // After the reload, the latest fetch URL should include status=active
        const latestUrl = fetch.mock.calls[fetch.mock.calls.length - 1][0];
        expect(latestUrl).toContain("status=active");
        expect(fetch.mock.calls.length).toBeGreaterThan(initialCalls);
    });

    it("setStatusFilter clears the filter when called with empty string", async () => {
        const props = { listPath: "/backend/projects/list" };
        const api = mountWithComposable(() => useProjectsListPage(props));
        await nextTick();
        await nextTick();

        api.setStatusFilter("active");
        await nextTick();
        api.setStatusFilter("");
        await nextTick();

        expect(api.statusFilter.value).toBe("");
        const latestUrl = fetch.mock.calls[fetch.mock.calls.length - 1][0];
        expect(latestUrl).not.toContain("status=");
    });
});
