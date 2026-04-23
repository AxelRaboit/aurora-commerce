import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { submitForm } from "@/utils/formSubmit.js";
import { accessRequestStatusBadge } from "@/utils/statusStyles.js";

export function useAdminAccessRequests(
    accessRequestsPath,
    approvePath,
    rejectPath,
    purgePath,
    csrfToken,
    initialAccessRequests,
) {
    const { t } = useI18n();

    const parsedAccessRequests = computed(
        () => initialAccessRequests ?? { items: [] },
    );

    const statusLabel = computed(() => ({
        pending: t("admin.access_requests.status_pending"),
        approved: t("admin.access_requests.status_approved"),
        rejected: t("admin.access_requests.status_rejected"),
    }));

    const pendingApprove = ref(null);
    const pendingReject = ref(null);
    const confirmPurge = ref(false);

    function openApproveModal(accessRequest) {
        pendingApprove.value = accessRequest;
    }

    function doApproveRequest() {
        if (!pendingApprove.value) return;
        submitForm(
            approvePath.replace("__id__", pendingApprove.value.id),
            csrfToken,
        );
        pendingApprove.value = null;
    }

    function doRejectRequest() {
        if (!pendingReject.value) return;
        submitForm(
            rejectPath.replace("__id__", pendingReject.value.id),
            csrfToken,
        );
        pendingReject.value = null;
    }

    function doPurge() {
        submitForm(purgePath, csrfToken);
        confirmPurge.value = false;
    }

    function accessRequestsUrl(page) {
        const url = new URL(accessRequestsPath, window.location.origin);
        if (page > 1) url.searchParams.set("page", page);
        return url.toString();
    }

    return {
        parsedAccessRequests,
        statusBadge: accessRequestStatusBadge,
        statusLabel,
        pendingApprove,
        pendingReject,
        confirmPurge,
        openApproveModal,
        doApproveRequest,
        doRejectRequest,
        doPurge,
        accessRequestsUrl,
    };
}
