/**
 * @vitest-environment happy-dom
 */
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent, h, nextTick } from "vue";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import { useProjectDetail } from "@/Module/Project/backend/composables/useProjectDetail.js";

const SHOW_PATH = "/backend/projects/__id__";

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

describe("useProjectDetail", () => {
    it("groups tasks by columnId and sorts each column by position", async () => {
        const api = mountWithComposable(() => useProjectDetail(SHOW_PATH));

        // Simulate loaded project with 2 columns and out-of-order tasks.
        api.activeProject.value = {
            id: 1,
            columns: [
                { id: 10, label: "Todo" },
                { id: 20, label: "Done" },
            ],
        };
        api.activeTasks.value = [
            { id: 1, columnId: 10, position: 2 },
            { id: 2, columnId: 10, position: 0 },
            { id: 3, columnId: 20, position: 1 },
            { id: 4, columnId: 10, position: 1 },
        ];
        await nextTick();

        const todo = api.tasksByColumn(10);
        expect(todo.map((task) => task.id)).toEqual([2, 4, 1]);
        expect(api.tasksByColumn(20).map((task) => task.id)).toEqual([3]);
    });

    it("returns empty array for unknown column id", async () => {
        const api = mountWithComposable(() => useProjectDetail(SHOW_PATH));
        await nextTick();
        expect(api.tasksByColumn(999)).toEqual([]);
    });

    it("openProject fetches and populates state on success", async () => {
        const payload = {
            success: true,
            project: { id: 7, columns: [{ id: 10 }] },
            tasks: [{ id: 1, columnId: 10, position: 0 }],
        };
        fetch.mockResolvedValueOnce({ ok: true, json: async () => payload });

        const api = mountWithComposable(() => useProjectDetail(SHOW_PATH));
        await api.openProject({ id: 7 });

        expect(fetch).toHaveBeenCalledWith(
            "/backend/projects/7",
            expect.objectContaining({ headers: expect.any(Object) }),
        );
        expect(api.activeProject.value.id).toBe(7);
        expect(api.activeTasks.value).toHaveLength(1);
    });

    it("closeDetail resets activeProject and activeTasks", async () => {
        const api = mountWithComposable(() => useProjectDetail(SHOW_PATH));
        api.activeProject.value = { id: 1, columns: [] };
        api.activeTasks.value = [{ id: 1 }];
        await nextTick();

        api.closeDetail();

        expect(api.activeProject.value).toBeNull();
        expect(api.activeTasks.value).toEqual([]);
    });
});
