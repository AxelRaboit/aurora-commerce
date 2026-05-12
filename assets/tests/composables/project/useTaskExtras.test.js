/**
 * @vitest-environment happy-dom
 */
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent, h, ref, nextTick } from "vue";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import { useTaskExtras } from "@project/backend/tasks/composables/useTaskExtras.js";

const PATHS = {
    commentCreate: "/backend/projects/tasks/__taskId__/comments",
    commentDelete: "/backend/projects/comments/__commentId__/delete",
    itemsReplace: "/backend/projects/tasks/__taskId__/items",
    timeEntryCreate: "/backend/projects/tasks/__taskId__/time-entries",
    timeEntryDelete: "/backend/projects/time-entries/__entryId__/delete",
    attachmentsAttach: "/backend/projects/tasks/__taskId__/attachments",
    attachmentDetach:
        "/backend/projects/tasks/__taskId__/attachments/__mediaId__",
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
    vi.stubGlobal(
        "fetch",
        vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ success: true }),
        }),
    );
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe("useTaskExtras — comments", () => {
    it("submitComment trims, POSTs, clears the field and reloads", async () => {
        const editingTask = ref({ id: 42, items: [] });
        const reload = vi.fn();
        const api = mountWithComposable(() =>
            useTaskExtras(PATHS, editingTask, reload),
        );

        api.newCommentContent.value = "  hello world  ";
        await api.submitComment();

        const [url, options] = fetch.mock.calls[0];
        expect(url).toBe("/backend/projects/tasks/42/comments");
        expect(JSON.parse(options.body)).toEqual({ content: "hello world" });
        expect(api.newCommentContent.value).toBe("");
        expect(reload).toHaveBeenCalled();
    });

    it("submitComment is a no-op when content is whitespace only", async () => {
        const editingTask = ref({ id: 1, items: [] });
        const api = mountWithComposable(() =>
            useTaskExtras(PATHS, editingTask, vi.fn()),
        );

        api.newCommentContent.value = "   ";
        await api.submitComment();
        expect(fetch).not.toHaveBeenCalled();
    });
});

describe("useTaskExtras — checklist", () => {
    it("addItem appends to localItems and persists the full list", async () => {
        const editingTask = ref({ id: 7, items: [] });
        const reload = vi.fn();
        const api = mountWithComposable(() =>
            useTaskExtras(PATHS, editingTask, reload),
        );
        await nextTick();

        api.newItemLabel.value = "  Buy milk  ";
        api.addItem();
        await nextTick();

        const [url, options] = fetch.mock.calls[0];
        expect(url).toBe("/backend/projects/tasks/7/items");
        const body = JSON.parse(options.body);
        expect(body.items).toEqual([{ label: "Buy milk", done: false }]);
        expect(api.newItemLabel.value).toBe("");
    });

    it("toggleItem flips done and re-persists", async () => {
        const item = { id: 1, label: "X", done: false, position: 0 };
        const editingTask = ref({ id: 1, items: [item] });
        const api = mountWithComposable(() =>
            useTaskExtras(PATHS, editingTask, vi.fn()),
        );
        await nextTick();

        api.toggleItem(api.localItems.value[0]);
        expect(api.localItems.value[0].done).toBe(true);
        expect(fetch).toHaveBeenCalled();
    });

    it("removeItem splices and re-persists", async () => {
        const editingTask = ref({
            id: 1,
            items: [
                { id: 1, label: "A", done: false, position: 0 },
                { id: 2, label: "B", done: false, position: 1 },
            ],
        });
        const api = mountWithComposable(() =>
            useTaskExtras(PATHS, editingTask, vi.fn()),
        );
        await nextTick();

        api.removeItem(0);
        expect(api.localItems.value).toHaveLength(1);
        expect(api.localItems.value[0].label).toBe("B");
    });
});

describe("useTaskExtras — time tracking", () => {
    it("logTime validates positive minutes before POST", async () => {
        const editingTask = ref({ id: 1, items: [] });
        const api = mountWithComposable(() =>
            useTaskExtras(PATHS, editingTask, vi.fn()),
        );

        api.newTimeEntry.value.minutes = 0;
        await api.logTime();
        expect(fetch).not.toHaveBeenCalled();

        api.newTimeEntry.value.minutes = 30;
        await api.logTime();
        expect(fetch).toHaveBeenCalled();
        const body = JSON.parse(fetch.mock.calls[0][1].body);
        expect(body.minutes).toBe(30);
    });
});

describe("useTaskExtras — attachments", () => {
    it("attachMedia POSTs media IDs", async () => {
        const editingTask = ref({ id: 8, items: [] });
        const api = mountWithComposable(() =>
            useTaskExtras(PATHS, editingTask, vi.fn()),
        );

        await api.attachMedia([3, 4, 5]);

        const [url, options] = fetch.mock.calls[0];
        expect(url).toBe("/backend/projects/tasks/8/attachments");
        expect(JSON.parse(options.body)).toEqual({ mediaIds: [3, 4, 5] });
    });

    it("attachMedia is a no-op for an empty list", async () => {
        const editingTask = ref({ id: 1, items: [] });
        const api = mountWithComposable(() =>
            useTaskExtras(PATHS, editingTask, vi.fn()),
        );

        await api.attachMedia([]);
        expect(fetch).not.toHaveBeenCalled();
    });

    it("detachMedia POSTs to the per-media endpoint", async () => {
        const editingTask = ref({ id: 9, items: [] });
        const api = mountWithComposable(() =>
            useTaskExtras(PATHS, editingTask, vi.fn()),
        );

        await api.detachMedia({ id: 33 });

        const [url] = fetch.mock.calls[0];
        expect(url).toBe("/backend/projects/tasks/9/attachments/33");
    });
});
