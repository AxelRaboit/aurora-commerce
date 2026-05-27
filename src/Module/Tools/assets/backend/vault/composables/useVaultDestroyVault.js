import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useVaultCrypto } from "@tools/backend/vault/composables/useVaultCrypto.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

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
    const { request } = useRequest();

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
            const json = await request(destroyPath, null, HttpMethod.Post);

            if (json?.success) {
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
