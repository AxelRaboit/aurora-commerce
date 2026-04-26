/**
 * Updates the current URL's `search` query parameter without reloading the page.
 * Empty values remove the parameter so URLs stay clean.
 *
 * Usage:
 *   <AppSearchInput v-model="search" v-on:search="(v) => { syncUrl(v); reset(); }" />
 */
export function useUrlSearchSync(paramName = "search") {
    return function syncUrl(value) {
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set(paramName, value);
        } else {
            url.searchParams.delete(paramName);
        }
        window.history.replaceState(null, "", url.toString());
    };
}
