/**
 * Full-page pagination helper for frontend list views.
 * Updates a URL query parameter and triggers a page reload.
 */
export function useUrlPagination(param = "page") {
    function goToPage(page) {
        const url = new URL(window.location.href);
        url.searchParams.set(param, String(page));
        window.location.href = url.toString();
    }
    return { goToPage };
}
