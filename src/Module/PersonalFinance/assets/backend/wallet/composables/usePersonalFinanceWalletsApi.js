import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Thin HTTP layer for PersonalFinance wallets backend. Centralises
 * `__id__` substitution and JSON envelope handling.
 */
export function usePersonalFinanceWalletsApi(initialWallets, paths) {
    const wallets = ref([...initialWallets]);
    const isSubmitting = ref(false);
    const errors = ref({});

    function resolvePath(template, id) {
        return template.replace("__id__", String(id));
    }

    function clearErrors() {
        errors.value = {};
    }

    async function request(method, url, body = null) {
        const options = { method, headers: { Accept: "application/json" } };
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

    async function createWallet(payload) {
        isSubmitting.value = true;
        clearErrors();
        try {
            const res = await request(
                HttpMethod.Post,
                paths.createWalletPath,
                payload,
            );
            if (!res.ok) {
                errors.value = res.payload?.errors ?? {};
                return null;
            }
            wallets.value.push(res.payload.wallet);
            return res.payload.wallet;
        } finally {
            isSubmitting.value = false;
        }
    }

    async function updateWallet(id, payload) {
        isSubmitting.value = true;
        clearErrors();
        try {
            const res = await request(
                HttpMethod.Post,
                resolvePath(paths.updateWalletPath, id),
                payload,
            );
            if (!res.ok) {
                errors.value = res.payload?.errors ?? {};
                return null;
            }
            const idx = wallets.value.findIndex((w) => w.id === id);
            if (idx !== -1) wallets.value[idx] = res.payload.wallet;
            return res.payload.wallet;
        } finally {
            isSubmitting.value = false;
        }
    }

    async function deleteWallet(id) {
        const res = await request(
            HttpMethod.Post,
            resolvePath(paths.deleteWalletPath, id),
        );
        if (res.ok) {
            wallets.value = wallets.value.filter((w) => w.id !== id);
        }
        return res.ok;
    }

    return {
        wallets,
        isSubmitting,
        errors,
        clearErrors,
        createWallet,
        updateWallet,
        deleteWallet,
    };
}
