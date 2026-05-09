import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";

export function useVaultSessionDuration() {
    const { t } = useI18n();

    const keepUnlocked = ref(false);
    const keepDuration = ref(30);

    const durationOptions = computed(() => [
        { value: 5, label: t("vault.session.duration_5min") },
        { value: 15, label: t("vault.session.duration_15min") },
        { value: 30, label: t("vault.session.duration_30min") },
        { value: 60, label: t("vault.session.duration_1h") },
        { value: 240, label: t("vault.session.duration_4h") },
        { value: 480, label: t("vault.session.duration_8h") },
        { value: 0, label: t("vault.session.duration_browser") },
    ]);

    /** Résout la durée finale à passer à crypto.persist() */
    function resolvedDuration() {
        return keepUnlocked.value ? keepDuration.value : 0;
    }

    return { keepUnlocked, keepDuration, durationOptions, resolvedDuration };
}
