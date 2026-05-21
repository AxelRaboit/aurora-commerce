import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Thin HTTP layer for the Post-it notes backend. Centralises `__id__`
 * substitution and JSON envelope handling so the page composable stays
 * focused on UI state — same shape as `useBlockNotesApi` /
 * `useMarkdownNotesApi`.
 */
export function usePostItNotesApi(props) {
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
        create: (payload) =>
            request(HttpMethod.Post, props.createPath, payload),
        update: (id, payload) =>
            request(
                HttpMethod.Post,
                resolvePath(props.updatePath, id),
                payload,
            ),
        move: (id, payload) =>
            request(HttpMethod.Post, resolvePath(props.movePath, id), payload),
        resize: (id, payload) =>
            request(
                HttpMethod.Post,
                resolvePath(props.resizePath, id),
                payload,
            ),
        delete: (id) =>
            request(HttpMethod.Post, resolvePath(props.deletePath, id)),
    };
}
