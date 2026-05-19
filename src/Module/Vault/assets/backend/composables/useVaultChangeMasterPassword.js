import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useForm } from "@shared/composables/form/useForm.js";
import { required } from "@shared/utils/validation/validators.js";
import { generateSalt } from "@vault/backend/composables/useVaultCrypto.js";
import { useVaultCrypto } from "@vault/backend/composables/useVaultCrypto.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function useVaultChangeMasterPassword(
    changePath,
    vaultConfig,
    entries,
    decryptedCache,
    onSuccess,
) {
    const { t } = useI18n();

    const show = ref(false);
    const step = ref(1); // 1 = verify current, 2 = new password, 3 = processing
    const currentPassword = ref("");
    const newPassword = ref("");
    const confirmPassword = ref("");
    const progress = ref(0);

    const { errors, validate, clearErrors, setErrors } = useForm();
    const { request } = useRequest();

    const tempCrypto = useVaultCrypto();

    function open() {
        step.value = 1;
        currentPassword.value = "";
        newPassword.value = "";
        confirmPassword.value = "";
        progress.value = 0;
        clearErrors();
        show.value = true;
    }

    function close() {
        if (step.value !== 3) show.value = false;
    }

    async function verifyCurrentPassword() {
        if (
            !validate({
                currentPassword: () =>
                    required(t("vault.setup.errors.password_required"))(
                        currentPassword.value,
                    ),
            })
        )
            return;

        const salt = vaultConfig.value?.argon2Salt;
        if (!salt) return;

        const firstEntry = entries.value[0];
        if (firstEntry) {
            const valid = await tempCrypto.verifyPassword(
                currentPassword.value,
                salt,
                firstEntry,
            );
            if (!valid) {
                setErrors({ currentPassword: t("vault.unlock.error") });
                return;
            }
        }

        await tempCrypto.unlock(currentPassword.value, salt);
        step.value = 2;
        clearErrors();
    }

    async function changePassword() {
        const valid = validate({
            newPassword: () =>
                required(t("vault.setup.errors.password_required"))(
                    newPassword.value,
                ),
            confirmPassword: () => {
                const err = required(t("vault.setup.errors.password_required"))(
                    confirmPassword.value,
                );
                if (err) return err;
                return newPassword.value !== confirmPassword.value
                    ? t("vault.setup.errors.passwords_mismatch")
                    : null;
            },
        });
        if (!valid) return;

        step.value = 3;
        progress.value = 0;

        try {
            const newSalt = generateSalt();
            const newCrypto = useVaultCrypto();
            await newCrypto.unlock(newPassword.value, newSalt);

            const reEncryptedEntries = [];
            const total = entries.value.length;

            for (let i = 0; i < total; i++) {
                const entry = entries.value[i];
                const fields = decryptedCache[entry.id] ?? {};
                const { encryptedData, iv } = await newCrypto.encrypt(fields);
                reEncryptedEntries.push({ id: entry.id, encryptedData, iv });
                progress.value = Math.round(((i + 1) / total) * 100);
            }

            const data = await request(changePath, {
                argon2Salt: newSalt,
                entries: reEncryptedEntries,
            });

            if (!data) {
                step.value = 2;
                return;
            }

            if (!data.success) {
                toast.error(t("shared.common.error"));
                step.value = 2;
                return;
            }

            toast.success(t("vault.change_password.success"));
            show.value = false;
            onSuccess(newSalt, newPassword.value, data.config);
        } catch {
            toast.error(t("shared.common.error"));
            step.value = 2;
        }
    }

    return {
        show,
        step,
        currentPassword,
        newPassword,
        confirmPassword,
        progress,
        errors,
        open,
        close,
        verifyCurrentPassword,
        changePassword,
    };
}
