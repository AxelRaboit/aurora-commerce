import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useVaultCrypto } from "@vault/backend/composables/useVaultCrypto.js";

export function useVaultDestroyVault(
    destroyPath,
    vaultConfig,
    entries,
    onSuccess,
) {
    const { t } = useI18n();

    const show = ref(false);
    const masterPassword = ref("");
    const loading = ref(false);
    const error = ref(null);

    const tempCrypto = useVaultCrypto();

    function open() {
        masterPassword.value = "";
        error.value = null;
        show.value = true;
    }

    function close() {
        if (!loading.value) show.value = false;
    }

    async function destroy() {
        error.value = null;

        if (!masterPassword.value) {
            error.value = t("vault.setup.errors.password_required");
            return;
        }

        const salt = vaultConfig.value?.argon2Salt;
        if (!salt) return;

        const firstEntry = entries.value[0];
        if (firstEntry) {
            const valid = await tempCrypto.verifyPassword(
                masterPassword.value,
                salt,
                firstEntry,
            );
            if (!valid) {
                error.value = t("vault.unlock.error");
                return;
            }
        }

        loading.value = true;

        try {
            const response = await fetch(destroyPath, { method: "POST" });
            const json = await response.json();

            if (json.success) {
                show.value = false;
                onSuccess?.();
            } else {
                error.value = t("vault.destroy.error");
            }
        } catch {
            error.value = t("vault.destroy.error");
        } finally {
            loading.value = false;
        }
    }

    return { show, masterPassword, loading, error, open, close, destroy };
}
