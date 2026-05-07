/**
 * @vitest-environment happy-dom
 */
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent, h, ref, nextTick } from "vue";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import { useColumnsManage } from "@/Module/Project/admin/composables/useColumnsManage.js";

const PATHS = {
    create: "/backend/projects/__id__/columns",
    update: "/backend/projects/columns/__columnId__/update",
    delete: "/backend/projects/columns/__columnId__/delete",
    reorder: "/backend/projects/__id__/columns/reorder",
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
    vi.stubGlobal("fetch", vi.fn());
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe("useColumnsManage", () => {
    it("openCreateColumn resets the form and shows the modal", async () => {
        const activeProject = ref({ id: 1, columns: [] });
        const reload = vi.fn();
        const api = mountWithComposable(() =>
            useColumnsManage(PATHS, activeProject, reload),
        );

        api.newColumn.value.label = "stale";
        api.openCreateColumn();
        expect(api.newColumn.value.label).toBe("");
        expect(api.showCreateColumn.value).toBe(true);
    });

    it("openRenameColumn loads existing label into the rename form", async () => {
        const activeProject = ref({ id: 1, columns: [] });
        const reload = vi.fn();
        const api = mountWithComposable(() =>
            useColumnsManage(PATHS, activeProject, reload),
        );

        api.openRenameColumn({ id: 5, label: "Backlog" });
        expect(api.editingColumn.value.id).toBe(5);
        expect(api.renameForm.value.label).toBe("Backlog");
        expect(api.showRenameColumn.value).toBe(true);
    });

    it("orderedColumns mirrors activeProject.columns reactively", async () => {
        const activeProject = ref({ id: 1, columns: [{ id: 1 }, { id: 2 }] });
        const api = mountWithComposable(() =>
            useColumnsManage(PATHS, activeProject, vi.fn()),
        );
        await nextTick();

        expect(api.orderedColumns.value.map((column) => column.id)).toEqual([
            1, 2,
        ]);

        activeProject.value = { id: 1, columns: [{ id: 3 }, { id: 4 }] };
        await nextTick();
        expect(api.orderedColumns.value.map((column) => column.id)).toEqual([
            3, 4,
        ]);
    });

    it("persistColumnsOrder POSTs the ordered IDs", async () => {
        fetch.mockResolvedValueOnce({ ok: true });
        const activeProject = ref({ id: 7, columns: [{ id: 1 }, { id: 2 }] });
        const api = mountWithComposable(() =>
            useColumnsManage(PATHS, activeProject, vi.fn()),
        );
        await nextTick();

        api.orderedColumns.value = [{ id: 2 }, { id: 1 }];
        await api.persistColumnsOrder();

        expect(fetch).toHaveBeenCalledTimes(1);
        const [url, options] = fetch.mock.calls[0];
        expect(url).toBe("/backend/projects/7/columns/reorder");
        expect(JSON.parse(options.body)).toEqual({ orderedIds: [2, 1] });
    });

    it("doDeleteColumn POSTs to the delete endpoint and reloads on success", async () => {
        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({ success: true }),
        });
        const activeProject = ref({ id: 1, columns: [] });
        const reload = vi.fn();
        const api = mountWithComposable(() =>
            useColumnsManage(PATHS, activeProject, reload),
        );

        api.confirmDeleteColumn({ id: 9, label: "X" });
        await api.doDeleteColumn();

        expect(fetch).toHaveBeenCalledWith(
            "/backend/projects/columns/9/delete",
            expect.objectContaining({ method: "POST" }),
        );
        expect(reload).toHaveBeenCalled();
        expect(api.pendingDeleteColumn.value).toBeNull();
    });
});
