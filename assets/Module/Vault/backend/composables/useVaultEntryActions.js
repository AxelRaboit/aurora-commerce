import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@shared/utils/http/buildPath.js";
import { useRequest } from "@shared/composables/http/useRequest.js";
import { useDelete } from "@shared/composables/form/useDelete.js";

export function useVaultEntryActions(
    crypto,
    entries,
    decryptedCache,
    deleteEntryPath,
    toggleFavoritePath,
) {
    const { t } = useI18n();
    const { request: toggleRequest } = useRequest();

    async function decryptIntoCache(entry) {
        try {
            decryptedCache[entry.id] = await crypto.decrypt(
                entry.encryptedData,
                entry.iv,
            );
        } catch {
            decryptedCache[entry.id] = {};
        }
    }

    async function onEntrySuccess(event, entry) {
        if (event === "created") {
            entries.value.unshift(entry);
            await decryptIntoCache(entry);
            toast.success(t("vault.entries.created"));
        } else if (event === "updated") {
            const index = entries.value.findIndex((e) => e.id === entry.id);
            if (index !== -1) entries.value[index] = entry;
            await decryptIntoCache(entry);
            toast.success(t("vault.entries.updated"));
        }
    }

    const {
        pendingDelete,
        loading: entryDeleteLoading,
        confirm: confirmDelete,
        submit: doDelete,
    } = useDelete(
        deleteEntryPath,
        (deletedId) => {
            entries.value = entries.value.filter(
                (entry) => entry.id !== deletedId,
            );
            delete decryptedCache[deletedId];
        },
        "vault.entries.deleted",
    );

    async function toggleFavorite(entry) {
        const url = buildPath(toggleFavoritePath, { id: entry.id });
        const data = await toggleRequest(url, {});
        if (data?.success) {
            entry.isFavorite = data.isFavorite;
            toast.success(
                data.isFavorite
                    ? t("vault.entries.favoriteAdded")
                    : t("vault.entries.favoriteRemoved"),
            );
        }
    }

    return {
        onEntrySuccess,
        pendingDelete,
        entryDeleteLoading,
        confirmDelete,
        doDelete,
        toggleFavorite,
    };
}
