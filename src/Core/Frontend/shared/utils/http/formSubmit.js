/**
 * Submit a POST form programmatically with a CSRF token and optional extra fields.
 * @param {string} action
 * @param {string} csrfToken
 * @param {Record<string, string>} [extraFields={}]
 */
export function submitForm(action, csrfToken, extraFields = {}) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = action;

    const csrf = document.createElement("input");
    csrf.type = "hidden";
    csrf.name = "_token";
    csrf.value = csrfToken;
    form.appendChild(csrf);

    for (const [name, value] of Object.entries(extraFields)) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = value ?? "";
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
}
