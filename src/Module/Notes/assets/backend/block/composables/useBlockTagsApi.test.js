import { describe, it, expect, vi, beforeEach } from "vitest";

const requestMock = vi.fn();
vi.mock("@/shared/composables/http/backend/useRequest.js", () => ({
    useRequest: () => ({
        loading: { value: false },
        request: requestMock,
    }),
}));

const { useBlockTagsApi } = await import("./useBlockTagsApi.js");

const props = {
    tagsListPath: "/api/block-tags",
    tagsRenamePath: "/api/block-tags/rename",
    tagsMergePath: "/api/block-tags/merge",
    tagsDeletePath: "/api/block-tags/delete",
};

beforeEach(() => {
    requestMock.mockReset();
});

describe("useBlockTagsApi", () => {
    it("issues a GET on list", async () => {
        requestMock.mockResolvedValue({ success: true, tags: [] });
        const api = useBlockTagsApi(props);

        await api.list();

        expect(requestMock).toHaveBeenCalledWith(
            "/api/block-tags",
            null,
            "GET",
        );
    });

    it("posts oldTag and newTag on rename", async () => {
        requestMock.mockResolvedValue({ success: true, affected: 2 });
        const api = useBlockTagsApi(props);

        await api.rename("old", "new");

        expect(requestMock).toHaveBeenCalledWith("/api/block-tags/rename", {
            oldTag: "old",
            newTag: "new",
        });
    });

    it("posts sourceTags and targetTag on merge", async () => {
        requestMock.mockResolvedValue({ success: true, affected: 3 });
        const api = useBlockTagsApi(props);

        await api.merge(["a", "b"], "c");

        expect(requestMock).toHaveBeenCalledWith("/api/block-tags/merge", {
            sourceTags: ["a", "b"],
            targetTag: "c",
        });
    });

    it("posts tag on remove", async () => {
        requestMock.mockResolvedValue({ success: true, affected: 1 });
        const api = useBlockTagsApi(props);

        await api.remove("gone");

        expect(requestMock).toHaveBeenCalledWith("/api/block-tags/delete", {
            tag: "gone",
        });
    });

    it("returns the request promise verbatim on list", async () => {
        const payload = { success: true, tags: [{ tag: "a", count: 1 }] };
        requestMock.mockResolvedValue(payload);
        const api = useBlockTagsApi(props);

        const result = await api.list();

        expect(result).toBe(payload);
    });

    it("exposes the shared loading ref from useRequest", () => {
        const api = useBlockTagsApi(props);

        expect(api.loading).toBeDefined();
        expect(api.loading.value).toBe(false);
    });
});
