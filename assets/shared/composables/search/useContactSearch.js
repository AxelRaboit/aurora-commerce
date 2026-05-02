import { ref } from "vue";

export function useContactSearch(searchPath) {
    const contactSearchQuery = ref("");
    const contactSearchResults = ref([]);
    const contactSearchOpen = ref(false);
    let abortController = null;
    let debounceTimer = null;
    let activeForm = null;

    async function searchContacts(query) {
        if (!searchPath) return;
        if (abortController) abortController.abort();
        if (!query.trim()) {
            contactSearchResults.value = [];
            contactSearchOpen.value = false;
            return;
        }
        abortController = new AbortController();
        try {
            const url = new URL(searchPath, window.location.origin);
            url.searchParams.set("search", query);
            const response = await fetch(url, {
                signal: abortController.signal,
            });
            const data = await response.json();
            contactSearchResults.value = data?.items ?? [];
            contactSearchOpen.value = true;
        } catch (error) {
            if (error.name !== "AbortError") contactSearchOpen.value = false;
        }
    }

    function onContactQueryInput(form, value) {
        activeForm = form;
        contactSearchQuery.value = value;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => searchContacts(value), 200);
    }

    function selectContact(contact) {
        if (!activeForm) return;
        activeForm.clientContactId = contact.id;
        activeForm.clientLabel = `${contact.fullName ?? contact.firstName + " " + contact.lastName}${contact.email ? " — " + contact.email : ""}`;
        contactSearchQuery.value = "";
        contactSearchResults.value = [];
        contactSearchOpen.value = false;
    }

    function clearContact(form) {
        form.clientContactId = null;
        form.clientLabel = null;
    }

    return {
        contactSearchQuery,
        contactSearchResults,
        contactSearchOpen,
        onContactQueryInput,
        selectContact,
        clearContact,
    };
}
