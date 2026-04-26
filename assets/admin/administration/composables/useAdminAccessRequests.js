import { HttpMethod } from "@/utils/httpMethod.js";
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { usePaginatedFetch } from "@/composables/usePaginatedFetch.js";
import {
    accessRequestStatusBadge,
    accessRequestStatusBadgeColor,
} from "@/utils/statusStyles.js";

export function useAdminAccessRequests(
    accessRequestsPath,
    approvePath,
    rejectPath,
    purgePath,
    csrfToken,
    initialAccessRequests,
) {
    const { t } = useI18n();

    const { items, loading, page, totalPages, load, goToPage, reset } =
        usePaginatedFetch(
            () => accessRequestsPath,
            () => ({}),
            null,
            initialAccessRequests,
        );

    onMounted(() => {
        if (!initialAccessRequests?.items?.length) {
            load();
        }
    });

    const statusLabel = ref({
        pending: t("admin.access_requests.status_pending"),
        approved: t("admin.access_requests.status_approved"),
        rejected: t("admin.access_requests.status_rejected"),
    });

    const pendingApprove = ref(null);
    const pendingReject = ref(null);
    const confirmPurge = ref(false);
    const acting = ref(false);

    function openApproveModal(accessRequest) {
        pendingApprove.value = accessRequest;
    }

    async function doApproveRequest() {
        if (!pendingApprove.value || acting.value) return;
        acting.value = true;
        try {
            const url = approvePath.replace("__id__", pendingApprove.value.id);
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    "X-CSRF-Token": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const data = await response.json();
            if (data.ok) {
                toast.success(
                    data.message ?? t("admin.access_requests.approved_toast"),
                );
                pendingApprove.value = null;
                await reset();
            } else {
                toast.error(t("common.error"));
            }
        } catch {
            toast.error(t("common.error"));
        } finally {
            acting.value = false;
        }
    }

    async function doRejectRequest() {
        if (!pendingReject.value || acting.value) return;
        acting.value = true;
        try {
            const url = rejectPath.replace("__id__", pendingReject.value.id);
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    "X-CSRF-Token": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const data = await response.json();
            if (data.ok) {
                toast.success(
                    data.message ?? t("admin.access_requests.rejected_toast"),
                );
                pendingReject.value = null;
                await reset();
            } else {
                toast.error(t("common.error"));
            }
        } catch {
            toast.error(t("common.error"));
        } finally {
            acting.value = false;
        }
    }

    async function doPurge() {
        if (acting.value) return;
        acting.value = true;
        try {
            const response = await fetch(purgePath, {
                method: HttpMethod.Post,
                headers: {
                    "X-CSRF-Token": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const data = await response.json();
            if (data.ok) {
                toast.success(
                    data.message ?? t("admin.access_requests.purged_toast"),
                );
                confirmPurge.value = false;
                await reset();
            } else {
                toast.error(t("common.error"));
            }
        } catch {
            toast.error(t("common.error"));
        } finally {
            acting.value = false;
        }
    }

    return {
        items,
        loading,
        page,
        totalPages,
        goToPage,
        statusBadge: accessRequestStatusBadge,
        statusBadgeColor: accessRequestStatusBadgeColor,
        statusLabel,
        pendingApprove,
        pendingReject,
        confirmPurge,
        acting,
        openApproveModal,
        doApproveRequest,
        doRejectRequest,
        doPurge,
    };
}
