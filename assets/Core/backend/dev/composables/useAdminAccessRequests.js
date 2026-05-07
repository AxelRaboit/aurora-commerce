import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { usePaginatedFetch } from "@/shared/composables/api/usePaginatedFetch.js";
import {
    accessRequestStatusBadge,
    accessRequestStatusBadgeColor,
} from "@/shared/utils/format/statusStyles.js";
import { AccessRequestStatus } from "@core/utils/enums/auth/accessRequestStatus.js";

export function useAdminAccessRequests(
    accessRequestsPath,
    approvePath,
    rejectPath,
    purgePath,
    csrfToken,
    initialAccessRequests,
    initialSearch = "",
) {
    const { t } = useI18n();

    const searchInput = ref(initialSearch);

    const { items, loading, page, totalPages, load, goToPage, reset } =
        usePaginatedFetch(
            () => accessRequestsPath,
            () => ({ search: searchInput.value || undefined }),
            null,
            initialAccessRequests,
        );

    function performSearch() {
        reset();
    }

    onMounted(() => {
        if (!initialAccessRequests?.items?.length) {
            load();
        }
    });

    const statusLabel = ref({
        [AccessRequestStatus.Pending]: t(
            "backend.access_requests.status_pending",
        ),
        [AccessRequestStatus.Approved]: t(
            "backend.access_requests.status_approved",
        ),
        [AccessRequestStatus.Rejected]: t(
            "backend.access_requests.status_rejected",
        ),
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
            const url = buildPath(approvePath, { id: pendingApprove.value.id });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    "X-CSRF-Token": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const data = await response.json();
            if (data.success) {
                toast.success(
                    data.message ?? t("backend.access_requests.approved_toast"),
                );
                pendingApprove.value = null;
                await reset();
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            acting.value = false;
        }
    }

    async function doRejectRequest() {
        if (!pendingReject.value || acting.value) return;
        acting.value = true;
        try {
            const url = buildPath(rejectPath, { id: pendingReject.value.id });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    "X-CSRF-Token": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const data = await response.json();
            if (data.success) {
                toast.success(
                    data.message ?? t("backend.access_requests.rejected_toast"),
                );
                pendingReject.value = null;
                await reset();
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
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
            if (data.success) {
                toast.success(
                    data.message ?? t("backend.access_requests.purged_toast"),
                );
                confirmPurge.value = false;
                await reset();
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
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
        load,
        reset,
        searchInput,
        performSearch,
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
