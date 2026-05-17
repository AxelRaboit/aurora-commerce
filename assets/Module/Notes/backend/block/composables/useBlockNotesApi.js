import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Thin HTTP layer for the Block notes backend. Centralises path
 * substitution (`__id__`) and JSON envelope handling so the components
 * stay focused on UI state — same shape as `useMarkdownNotesApi`.
 */
export function useBlockNotesApi(props) {
    function resolvePath(template, id) {
        return template.replace("__id__", String(id));
    }

    async function request(method, url, body = null) {
        const options = {
            method,
            headers: { Accept: "application/json" },
        };
        if (body !== null) {
            options.headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(body);
        }
        const response = await fetch(url, options);
        const payload = await response.json().catch(() => ({}));
        return {
            ok: response.ok && payload.success !== false,
            status: response.status,
            payload,
        };
    }

    return {
        list: () => request(HttpMethod.Get, props.listPath),
        show: (id) => request(HttpMethod.Get, resolvePath(props.showPath, id)),
        create: (payload) =>
            request(HttpMethod.Post, props.createPath, payload),
        update: (id, payload) =>
            request(
                HttpMethod.Post,
                resolvePath(props.updatePath, id),
                payload,
            ),
        remove: (id) =>
            request(HttpMethod.Post, resolvePath(props.deletePath, id), {}),
        move: (id, parentId) =>
            request(HttpMethod.Post, resolvePath(props.movePath, id), {
                parentId,
            }),
        searchContent: (query) =>
            request(
                HttpMethod.Get,
                `${props.searchPath}?q=${encodeURIComponent(query)}`,
            ),
        uploadImage: async (file) => {
            const formData = new FormData();
            formData.append("image", file);
            const response = await fetch(props.imageUploadPath, {
                method: HttpMethod.Post,
                headers: { Accept: "application/json" },
                body: formData,
            });
            const payload = await response.json().catch(() => ({}));
            return {
                ok: response.ok && payload.success !== false,
                status: response.status,
                payload,
            };
        },
    };
}
