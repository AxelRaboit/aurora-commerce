/**
 * @vitest-environment happy-dom
 */
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent, h, ref } from "vue";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import { useTasksKanban } from "@/Module/Project/admin/composables/useTasksKanban.js";

const REORDER_PATH = "/backend/projects/__id__/tasks/reorder";

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
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue({ ok: true }));
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe("useTasksKanban", () => {
    it("persistColumnOrder POSTs ordered task IDs and target column", async () => {
        const activeProject = ref({ id: 5 });
        const localColumns = ref({
            10: [
                { id: 1, columnId: 10 },
                { id: 2, columnId: 10 },
            ],
        });

        const api = mountWithComposable(() =>
            useTasksKanban(REORDER_PATH, activeProject, localColumns),
        );
        await api.persistColumnOrder(10);

        expect(fetch).toHaveBeenCalledTimes(1);
        const [url, options] = fetch.mock.calls[0];
        expect(url).toBe("/backend/projects/5/tasks/reorder");
        expect(options.method).toBe("POST");
        const body = JSON.parse(options.body);
        expect(body.columnId).toBe(10);
        expect(body.orderedIds).toEqual([1, 2]);
    });

    it("updates each task's columnId locally before persisting", async () => {
        const activeProject = ref({ id: 1 });
        // A task that was just dropped into column 20 still has its old columnId.
        const localColumns = ref({
            20: [{ id: 99, columnId: 10 }],
        });

        const api = mountWithComposable(() =>
            useTasksKanban(REORDER_PATH, activeProject, localColumns),
        );
        await api.onColumnAdd(20);

        expect(localColumns.value[20][0].columnId).toBe(20);
    });

    it("noop when there is no active project", async () => {
        const activeProject = ref(null);
        const localColumns = ref({ 10: [] });

        const api = mountWithComposable(() =>
            useTasksKanban(REORDER_PATH, activeProject, localColumns),
        );
        await api.persistColumnOrder(10);

        expect(fetch).not.toHaveBeenCalled();
    });
});
