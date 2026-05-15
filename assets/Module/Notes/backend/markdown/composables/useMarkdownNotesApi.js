/**
 * Thin HTTP layer for the Markdown notes backend. Centralizes the path
 * substitution (`__id__`) and JSON envelope handling so the components stay
 * focused on UI state.
 */
export function useMarkdownNotesApi(props) {
    function resolvePath(template, id) {
        return template.replace('__id__', String(id));
    }

    async function request(method, url, body = null) {
        const options = {
            method,
            headers: { Accept: 'application/json' },
        };
        if (body !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        const response = await fetch(url, options);
        const payload = await response.json().catch(() => ({}));
        return { ok: response.ok && payload.success !== false, status: response.status, payload };
    }

    return {
        list: () => request('GET', props.listPath),
        show: (id) => request('GET', resolvePath(props.showPath, id)),
        create: (payload) => request('POST', props.createPath, payload),
        update: (id, payload) => request('POST', resolvePath(props.updatePath, id), payload),
        remove: (id) => request('POST', resolvePath(props.deletePath, id), {}),
        move: (id, parentId) => request('POST', resolvePath(props.movePath, id), { parentId }),
        reorder: (ids) => request('POST', props.reorderPath, { ids }),
        backlinks: (id) => request('GET', resolvePath(props.backlinksPath, id)),
        unlinkedMentions: (id) => request('GET', resolvePath(props.unlinkedMentionsPath, id)),
        graph: () => request('GET', props.graphPath),
    };
}
