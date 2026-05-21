import { describe, it, expect, vi, beforeEach } from "vitest";
import { usePostItNotesApi } from "./usePostItNotesApi.js";

const props = {
    listPath: "/api/post-it/list",
    createPath: "/api/post-it/create",
    updatePath: "/api/post-it/__id__/update",
    movePath: "/api/post-it/__id__/move",
    resizePath: "/api/post-it/__id__/resize",
    deletePath: "/api/post-it/__id__/delete",
};

function mockFetch(
    payload = { success: true },
    { ok = true, status = 200 } = {},
) {
    return vi.fn().mockResolvedValue({
        ok,
        status,
        json: () => Promise.resolve(payload),
    });
}

beforeEach(() => {
    globalThis.fetch = vi.fn();
});

describe("usePostItNotesApi", () => {
    it("issues a GET on list with no body", async () => {
        globalThis.fetch = mockFetch({ success: true, notes: [] });
        const api = usePostItNotesApi(props);

        const response = await api.list();

        expect(globalThis.fetch).toHaveBeenCalledOnce();
        const [url, options] = globalThis.fetch.mock.calls[0];
        expect(url).toBe("/api/post-it/list");
        expect(options.method).toBe("GET");
        expect(options.body).toBeUndefined();
        expect(response.ok).toBe(true);
        expect(response.payload.notes).toEqual([]);
    });

    it("POSTs a JSON body on create", async () => {
        globalThis.fetch = mockFetch({ success: true, note: { id: 1 } });
        const api = usePostItNotesApi(props);

        await api.create({ title: "T", color: "#FFEB3B" });

        const [url, options] = globalThis.fetch.mock.calls[0];
        expect(url).toBe("/api/post-it/create");
        expect(options.method).toBe("POST");
        expect(options.headers["Content-Type"]).toBe("application/json");
        expect(JSON.parse(options.body)).toEqual({
            title: "T",
            color: "#FFEB3B",
        });
    });

    it("substitutes __id__ in update / move / resize / delete paths", async () => {
        globalThis.fetch = mockFetch();
        const api = usePostItNotesApi(props);

        await api.update(42, { title: "X" });
        await api.move(17, { positionX: 1, positionY: 2 });
        await api.resize(99, { width: 200, height: 200 });
        await api.delete(7);

        const calls = globalThis.fetch.mock.calls.map(([url]) => url);
        expect(calls).toEqual([
            "/api/post-it/42/update",
            "/api/post-it/17/move",
            "/api/post-it/99/resize",
            "/api/post-it/7/delete",
        ]);
    });

    it("flags ok=false when payload.success is false", async () => {
        // Server returned 200 with a logical error in the envelope —
        // the caller should treat the operation as failed without throwing.
        globalThis.fetch = mockFetch({ success: false, error: "bad" });
        const api = usePostItNotesApi(props);

        const response = await api.create({});

        expect(response.ok).toBe(false);
        expect(response.payload.error).toBe("bad");
    });

    it("flags ok=false on non-2xx HTTP status", async () => {
        globalThis.fetch = mockFetch(
            { success: true },
            { ok: false, status: 500 },
        );
        const api = usePostItNotesApi(props);

        const response = await api.list();

        expect(response.ok).toBe(false);
        expect(response.status).toBe(500);
    });

    it("recovers from invalid JSON with an empty payload object", async () => {
        // .json() throws on malformed responses — the composable swallows it
        // so the caller can still inspect `ok` / `status` without a crash.
        globalThis.fetch = vi.fn().mockResolvedValue({
            ok: true,
            status: 200,
            json: () => Promise.reject(new Error("invalid json")),
        });
        const api = usePostItNotesApi(props);

        const response = await api.list();

        expect(response.payload).toEqual({});
    });

    it("delete sends a POST with no body (server reads from the URL)", async () => {
        globalThis.fetch = mockFetch();
        const api = usePostItNotesApi(props);

        await api.delete(5);

        const [, options] = globalThis.fetch.mock.calls[0];
        expect(options.method).toBe("POST");
        expect(options.body).toBeUndefined();
    });
});
