import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useProfilePhoto(uploadPath, deletePath, initialPhotoUrl = "") {
    const { t } = useI18n();
    const photoUrl = ref(initialPhotoUrl);
    const photoLoading = ref(false);

    async function onPhotoSelected(file) {
        if (!file) return;
        photoLoading.value = true;
        try {
            const formData = new FormData();
            formData.append("photo", file);
            const response = await fetch(uploadPath, {
                method: HttpMethod.Post,
                body: formData,
            });
            const data = await response.json();
            if (!data.success) {
                toast.error(
                    t(
                        data.errors?.photo ??
                            data.error ??
                            "shared.common.error",
                    ),
                );
                return;
            }
            photoUrl.value = data.profilePhotoUrl ?? "";
            toast.success(t("backend.users.photo.uploaded"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            photoLoading.value = false;
        }
    }

    async function removePhoto() {
        photoLoading.value = true;
        try {
            const response = await fetch(deletePath, {
                method: HttpMethod.Post,
            });
            const data = await response.json();
            if (!data.success) {
                toast.error(t(data.error ?? "shared.common.error"));
                return;
            }
            photoUrl.value = "";
            toast.success(t("backend.users.photo.removed"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            photoLoading.value = false;
        }
    }

    return { photoUrl, photoLoading, onPhotoSelected, removePhoto };
}
