import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function useVaultUnlockLifecycle(
    crypto,
    vaultConfig,
    entries,
    decryptedCache,
) {
    const { t } = useI18n();
    const isUnlocked = ref(false);

    async function unlockAndDecrypt() {
        for (const entry of entries.value) {
            try {
                decryptedCache[entry.id] = await crypto.decrypt(
                    entry.encryptedData,
                    entry.iv,
                );
            } catch {
                decryptedCache[entry.id] = {};
            }
        }
        isUnlocked.value = true;
    }

    async function onSetupComplete({
        salt,
        masterPassword,
        config,
        keepUnlocked,
        keepDuration,
    }) {
        vaultConfig.value = config;
        await crypto.unlock(masterPassword, salt);
        if (keepUnlocked) await crypto.persist(keepDuration);
        isUnlocked.value = true;
    }

    async function onUnlock({
        masterPassword,
        onError,
        onSuccess,
        keepUnlocked,
        keepDuration,
    }) {
        const saltBase64 = vaultConfig.value?.argon2Salt;
        if (!saltBase64) {
            onError();
            return;
        }

        const firstEntry = entries.value[0];
        if (firstEntry) {
            const valid = await crypto.verifyPassword(
                masterPassword,
                saltBase64,
                firstEntry,
            );
            if (!valid) {
                onError();
                return;
            }
        }

        const success = await crypto.unlock(masterPassword, saltBase64);
        if (!success) {
            onError();
            return;
        }

        await unlockAndDecrypt();
        if (keepUnlocked) await crypto.persist(keepDuration);
        onSuccess();
    }

    function lockVault(decryptedCacheRef) {
        crypto.lock();
        isUnlocked.value = false;
        Object.keys(decryptedCacheRef).forEach(
            (key) => delete decryptedCacheRef[key],
        );
    }

    onMounted(async () => {
        if (!vaultConfig.value) return;
        if (await crypto.restoreFromSession()) {
            await unlockAndDecrypt();
            const expiry = crypto.sessionExpiry();
            if (expiry === null) {
                toast.success(t("vault.session.restored_browser"));
            } else {
                const remainingMs = expiry - Date.now();
                const remainingMin = Math.ceil(remainingMs / 60_000);
                const h = Math.floor(remainingMin / 60);
                const m = (remainingMin % 60).toString().padStart(2, "0");
                toast.success(
                    remainingMin >= 60
                        ? t("vault.session.restored_hours", { h, m })
                        : t("vault.session.restored_minutes", {
                              m: remainingMin,
                          }),
                );
            }
        }
    });

    return { isUnlocked, onSetupComplete, onUnlock, lockVault };
}
