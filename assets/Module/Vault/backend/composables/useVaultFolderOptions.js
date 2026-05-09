import { computed } from "vue";
import { useI18n } from "vue-i18n";

/**
 * @param {import('vue').Ref<Array>} folders
 */
export function useVaultFolderOptions(folders) {
    const { t } = useI18n();

    const folderOptions = computed(() => [
        { value: null, label: t("vault.entries.folderNone") },
        ...folders.value.map((folder) => ({
            value: folder.id,
            label: folder.name,
        })),
    ]);

    return { folderOptions };
}
