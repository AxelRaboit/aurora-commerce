import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { useBlockNotesApi } from "./useBlockNotesApi.js";

const props = {
    listPath: "/api/block-notes",
    showPath: "/api/block-notes/__id__",
    createPath: "/api/block-notes/new",
    updatePath: "/api/block-notes/__id__/update",
    deletePath: "/api/block-notes/__id__/delete",
    movePath: "/api/block-notes/__id__/move",
    searchPath: "/api/block-notes/search",
    imageUploadPath: "/api/block-notes/upload",
};

function jsonResponse(body, { status = 200, ok = true } = {}) {
    return {
        ok,
        status,
        json: () => Promise.resolve(body),
    };
}

let fetchMock;

beforeEach(() => {
    fetchMock = vi.fn();
    globalThis.fetch = fetchMock;
});

afterEach(() => {
    vi.restoreAllMocks();
});

describe("useBlockNotesApi", () => {
    it("issues a GET on list with JSON Accept header", async () => {
        fetchMock.mockResolvedValue(
            jsonResponse({ success: true, notes: [{ id: 1 }] }),
        );
        const api = useBlockNotesApi(props);

        const { ok, status, payload } = await api.list();

        expect(fetchMock).toHaveBeenCalledTimes(1);
        const [url, options] = fetchMock.mock.calls[0];
        expect(url).toBe("/api/block-notes");
        expect(options.method).toBe("GET");
        expect(options.headers).toEqual({ Accept: "application/json" });
        expect(options.body).toBeUndefined();
        expect(ok).toBe(true);
        expect(status).toBe(200);
        expect(payload.notes).toEqual([{ id: 1 }]);
    });

    it("substitutes __id__ when calling show", async () => {
        fetchMock.mockResolvedValue(
            jsonResponse({ success: true, note: { id: 42 } }),
        );
        const api = useBlockNotesApi(props);

        await api.show(42);

        const [url, options] = fetchMock.mock.calls[0];
        expect(url).toBe("/api/block-notes/42");
        expect(options.method).toBe("GET");
    });

    it("POSTs a JSON body on create with Content-Type set", async () => {
        fetchMock.mockResolvedValue(
            jsonResponse({ success: true, note: { id: 7 } }),
        );
        const api = useBlockNotesApi(props);

        await api.create({ title: "hi", tags: [], blocks: [] });

        const [url, options] = fetchMock.mock.calls[0];
        expect(url).toBe("/api/block-notes/new");
        expect(options.method).toBe("POST");
        expect(options.headers["Content-Type"]).toBe("application/json");
        expect(JSON.parse(options.body)).toEqual({
            title: "hi",
            tags: [],
            blocks: [],
        });
    });

    it("substitutes __id__ on update and posts the payload as JSON", async () => {
        fetchMock.mockResolvedValue(jsonResponse({ success: true }));
        const api = useBlockNotesApi(props);

        await api.update(5, { title: "x" });

        const [url, options] = fetchMock.mock.calls[0];
        expect(url).toBe("/api/block-notes/5/update");
        expect(options.method).toBe("POST");
        expect(JSON.parse(options.body)).toEqual({ title: "x" });
    });

    it("posts an empty object on remove", async () => {
        fetchMock.mockResolvedValue(jsonResponse({ success: true }));
        const api = useBlockNotesApi(props);

        await api.remove(9);

        const [url, options] = fetchMock.mock.calls[0];
        expect(url).toBe("/api/block-notes/9/delete");
        expect(options.method).toBe("POST");
        expect(JSON.parse(options.body)).toEqual({});
    });

    it("posts parentId on move, including null for root", async () => {
        fetchMock.mockResolvedValue(jsonResponse({ success: true }));
        const api = useBlockNotesApi(props);

        await api.move(3, 1);
        expect(fetchMock.mock.calls[0][0]).toBe("/api/block-notes/3/move");
        expect(JSON.parse(fetchMock.mock.calls[0][1].body)).toEqual({
            parentId: 1,
        });

        await api.move(3, null);
        expect(JSON.parse(fetchMock.mock.calls[1][1].body)).toEqual({
            parentId: null,
        });
    });

    it("url-encodes the query on searchContent", async () => {
        fetchMock.mockResolvedValue(jsonResponse({ success: true, ids: [] }));
        const api = useBlockNotesApi(props);

        await api.searchContent("a b&c");

        expect(fetchMock.mock.calls[0][0]).toBe(
            "/api/block-notes/search?q=a%20b%26c",
        );
        expect(fetchMock.mock.calls[0][1].method).toBe("GET");
    });

    it("marks ok=false when payload.success is false even if HTTP 200", async () => {
        fetchMock.mockResolvedValue(jsonResponse({ success: false }));
        const api = useBlockNotesApi(props);

        const { ok, status } = await api.list();

        expect(ok).toBe(false);
        expect(status).toBe(200);
    });

    it("marks ok=false on non-2xx HTTP response", async () => {
        fetchMock.mockResolvedValue(
            jsonResponse({}, { ok: false, status: 500 }),
        );
        const api = useBlockNotesApi(props);

        const { ok, status } = await api.list();

        expect(ok).toBe(false);
        expect(status).toBe(500);
    });

    it("returns an empty payload object when json() throws", async () => {
        fetchMock.mockResolvedValue({
            ok: true,
            status: 204,
            json: () => Promise.reject(new Error("no body")),
        });
        const api = useBlockNotesApi(props);

        const { ok, payload } = await api.list();

        // ok stays true (HTTP 2xx, no success:false), payload is {}
        expect(ok).toBe(true);
        expect(payload).toEqual({});
    });

    it("uploads an image as multipart FormData (no JSON Content-Type)", async () => {
        fetchMock.mockResolvedValue(
            jsonResponse({ success: true, filename: "x.png", url: "/u/x.png" }),
        );
        const api = useBlockNotesApi(props);

        const file = new File(["abc"], "x.png", { type: "image/png" });
        const { ok, payload } = await api.uploadImage(file);

        const [url, options] = fetchMock.mock.calls[0];
        expect(url).toBe("/api/block-notes/upload");
        expect(options.method).toBe("POST");
        expect(options.headers).toEqual({ Accept: "application/json" });
        // FormData body, NOT a JSON string
        expect(options.body).toBeInstanceOf(FormData);
        expect(options.body.get("image")).toBe(file);
        expect(ok).toBe(true);
        expect(payload.url).toBe("/u/x.png");
    });
});
