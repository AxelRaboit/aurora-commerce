<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { Trash2 } from "lucide-vue-next";
import { useAdminAccessRequests } from "@core/admin/administration/composables/useAdminAccessRequests.js";
import AdminAccessRequestStatusBadge from "@core/admin/administration/AdminAccessRequestStatusBadge.vue";
import AdminAccessRequestActions from "@core/admin/administration/AdminAccessRequestActions.vue";

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const props = defineProps({
    accessRequestsPath: { type: String, required: true },
    accessRequestApprovePath: { type: String, required: true },
    accessRequestRejectPath: { type: String, required: true },
    accessRequestPurgePath: { type: String, required: true },
    csrfToken: { type: String, default: "" },
    initialData: { type: Object, default: null },
    initialSearch: { type: String, default: "" },
});

const accessRequests = useAdminAccessRequests(
    props.accessRequestsPath,
    props.accessRequestApprovePath,
    props.accessRequestRejectPath,
    props.accessRequestPurgePath,
    props.csrfToken,
    props.initialData,
    props.initialSearch,
);

onMounted(() => {
    if (!accessRequests.items.value?.length) accessRequests.load();
});
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput
                v-model="accessRequests.searchInput.value"
                :placeholder="t('admin.access_requests.searchPlaceholder')"
                v-on:search="accessRequests.performSearch"
            />
            <AppButton variant="danger" size="md" class="w-full sm:w-auto" v-on:click="accessRequests.confirmPurge.value = true">
                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                {{ t('admin.access_requests.purge') }}
            </AppButton>
        </div>

        <div class="sm:hidden space-y-3">
            <p v-if="!accessRequests.items.value?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.access_requests.empty') }}</p>
            <div v-for="accessRequest in accessRequests.items.value" :key="accessRequest.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-primary truncate">{{ accessRequest.requesterName ?? '-' }}</p>
                        <p class="text-xs text-secondary truncate">{{ accessRequest.requesterEmail }}</p>
                    </div>
                    <AdminAccessRequestStatusBadge
                        :access-request="accessRequest"
                        :status-label="accessRequests.statusLabel.value"
                        class="shrink-0"
                    />
                </div>
                <p v-if="accessRequest.message" class="text-sm text-secondary">{{ accessRequest.message }}</p>
                <div class="flex items-center justify-between pt-1 border-t border-line">
                    <p class="text-xs text-muted">{{ formatDateShort(accessRequest.createdAt) }} · expire {{ formatDateShort(accessRequest.expiresAt) }}</p>
                    <div class="flex items-center gap-1">
                        <AdminAccessRequestActions
                            :access-request="accessRequest"
                            v-on:approve="accessRequests.openApproveModal"
                            v-on:reject="(ar) => (accessRequests.pendingReject.value = ar)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 border-b border-line">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary">{{ t('admin.access_requests.requester') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">{{ t('admin.access_requests.message') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary">{{ t('admin.access_requests.status') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ t('admin.access_requests.date') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ t('admin.access_requests.expires') }}</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-primary">{{ t('admin.users.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="accessRequest in accessRequests.items.value" :key="accessRequest.id" class="hover:bg-surface-2/50 transition-colors">
                        <td class="px-6 py-3">
                            <p class="font-medium text-primary">{{ accessRequest.requesterName ?? '-' }}</p>
                            <p class="text-xs text-secondary">{{ accessRequest.requesterEmail }}</p>
                        </td>
                        <td class="px-6 py-3 max-w-xs hidden md:table-cell">
                            <p class="text-sm text-secondary truncate">{{ accessRequest.message ?? '-' }}</p>
                        </td>
                        <td class="px-6 py-3">
                            <AdminAccessRequestStatusBadge
                                :access-request="accessRequest"
                                :status-label="accessRequests.statusLabel.value"
                            />
                        </td>
                        <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(accessRequest.createdAt) }}</td>
                        <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(accessRequest.expiresAt) }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <AdminAccessRequestActions
                                    :access-request="accessRequest"
                                    v-on:approve="accessRequests.openApproveModal"
                                    v-on:reject="(ar) => (accessRequests.pendingReject.value = ar)"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!accessRequests.items.value?.length">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t('admin.access_requests.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination
            v-if="accessRequests.totalPages.value > 1"
            :page="accessRequests.page.value"
            :total-pages="accessRequests.totalPages.value"
            v-on:change="accessRequests.goToPage"
        />

        <AppModal :show="!!accessRequests.pendingApprove.value" max-width="sm" v-on:close="accessRequests.pendingApprove.value = null">
            <p class="text-sm text-primary">{{ t('admin.access_requests.approveConfirm', { name: accessRequests.pendingApprove.value?.requesterName ?? accessRequests.pendingApprove.value?.requesterEmail }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="accessRequests.pendingApprove.value = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" size="md" v-on:click="accessRequests.doApproveRequest">{{ t('admin.access_requests.approve') }}</AppButton>
            </div>
        </AppModal>

        <AppModal :show="!!accessRequests.pendingReject.value" max-width="sm" v-on:close="accessRequests.pendingReject.value = null">
            <p class="text-sm text-primary">{{ t('admin.access_requests.rejectConfirm', { name: accessRequests.pendingReject.value?.requesterName ?? accessRequests.pendingReject.value?.requesterEmail }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="accessRequests.pendingReject.value = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="accessRequests.doRejectRequest">{{ t('admin.access_requests.reject') }}</AppButton>
            </div>
        </AppModal>

        <AppModal :show="accessRequests.confirmPurge.value" max-width="sm" v-on:close="accessRequests.confirmPurge.value = false">
            <p class="text-sm text-primary">{{ t('admin.access_requests.purgeConfirm') }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="accessRequests.confirmPurge.value = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="accessRequests.doPurge">{{ t('admin.access_requests.purge') }}</AppButton>
            </div>
        </AppModal>
    </div>
</template>
