<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { formatCurrency } from "@/shared/utils/format/formatPrice.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { Truck, PackageCheck, Ban, Clock } from "lucide-vue-next";
import { toast } from "vue-sonner";

const { t } = useI18n();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    order: { type: Object, required: true },
    activity: { type: Array, default: () => [] },
    backPath: { type: String, required: true },
    updateStatusPath: { type: String, required: true },
    canManage: { type: Boolean, default: false },
});

const order = ref({ ...props.order });
const activity = ref([...props.activity]);
const { loading, request } = useApiRequest();

const statusBadge = (status) => ({
    pending: "amber",
    paid: "sky",
    shipped: "accent",
    delivered: "emerald",
    cancelled: "rose",
}[status] ?? "slate");

const formattedTotal = computed(() => formatCurrency(order.value.total, order.value.currency));
const formatLineSubtotal = (line) => formatCurrency(line.subtotal, order.value.currency);

// Allowed forward transitions per current status. Digital-only orders (requiresShipping=false)
// skip the shipped step entirely — they go paid → delivered (i.e. fulfilled).
const availableTransitions = computed(() => {
    const requiresShipping = order.value.requiresShipping ?? true;
    const map = {
        pending: [],
        paid: requiresShipping
            ? [{ status: "shipped", label: t("admin.ecommerce.orders.actions.markShipped"), icon: Truck, color: "accent" }]
            : [{ status: "delivered", label: t("admin.ecommerce.orders.actions.markFulfilled"), icon: PackageCheck, color: "emerald" }],
        shipped: [{ status: "delivered", label: t("admin.ecommerce.orders.actions.markDelivered"), icon: PackageCheck, color: "emerald" }],
        delivered: [],
        cancelled: [],
    };
    return map[order.value.status] ?? [];
});

const canCancel = computed(() => !["delivered", "cancelled"].includes(order.value.status));

const pendingTransition = ref(null);
function confirmTransition(target) {
    pendingTransition.value = target;
}

async function applyTransition() {
    if (!pendingTransition.value) return;
    const target = pendingTransition.value;
    const data = await request(props.updateStatusPath, { status: target.status }, HttpMethod.Patch);
    pendingTransition.value = null;
    if (data?.success) {
        order.value = { ...order.value, ...data.order };
        toast.success(t("admin.ecommerce.orders.actions.transition_success"));
        // Refresh activity from page (cheap UX: just reload).
        // We could also refetch the audit log endpoint; for now a soft hint to reload is enough.
        prependActivity(target.status, order.value.status);
    } else if (data?.error) {
        toast.error(data.error);
    }
}

function prependActivity(targetStatus) {
    activity.value = [
        {
            id: Date.now(),
            module: "ecommerce",
            action: targetStatus === "cancelled" ? "order.cancelled" : `order.${targetStatus}`,
            entityType: "Order",
            entityId: order.value.id,
            userId: null,
            userEmail: null,
            userName: t("admin.ecommerce.orders.actions.you"),
            data: { number: order.value.number },
            createdAt: new Date().toISOString(),
        },
        ...activity.value,
    ];
}

function actionLabel(action) {
    const map = {
        "order.created": t("admin.ecommerce.orders.activity.created"),
        "order.paid": t("admin.ecommerce.orders.activity.paid"),
        "order.shipped": t("admin.ecommerce.orders.activity.shipped"),
        "order.delivered": t("admin.ecommerce.orders.activity.delivered"),
        "order.cancelled": t("admin.ecommerce.orders.activity.cancelled"),
    };
    return map[action] ?? action;
}
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-surface border border-line rounded-lg p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold text-primary font-mono">{{ order.number }}</h2>
                        <p class="text-sm text-secondary">{{ formatDateTime(order.createdAt) }}</p>
                    </div>
                    <AppBadge :color="statusBadge(order.status)" class="self-start">{{ t(`admin.ecommerce.orders.status.${order.status}`) }}</AppBadge>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm border-t border-line pt-4">
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('admin.ecommerce.orders.customer') }}</dt>
                        <dd class="text-primary">{{ order.name }}</dd>
                        <dd class="text-secondary">{{ order.email }}</dd>
                    </div>
                    <div v-if="order.requiresShipping">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('admin.ecommerce.orders.shipping') }}</dt>
                        <dd class="text-secondary">{{ order.addressLine1 }}<span v-if="order.addressLine2">, {{ order.addressLine2 }}</span></dd>
                        <dd class="text-secondary">{{ order.postalCode }} {{ order.city }} ({{ order.country }})</dd>
                    </div>
                    <div v-else>
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('admin.ecommerce.orders.fulfillment') }}</dt>
                        <dd class="text-secondary">{{ t('admin.ecommerce.orders.no_shipping_required') }}</dd>
                    </div>
                    <div v-if="order.notes" class="sm:col-span-2">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('admin.ecommerce.orders.notes') }}</dt>
                        <dd class="text-secondary whitespace-pre-wrap">{{ order.notes }}</dd>
                    </div>
                </dl>
            </div>

            <div class="sm:hidden space-y-2">
                <div v-for="line in order.lines" :key="line.id" class="bg-surface border border-line rounded-xl p-4 space-y-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary">{{ line.title }}</p>
                            <p v-if="line.sku" class="text-xs text-muted font-mono mt-0.5">{{ line.sku }}</p>
                        </div>
                        <span class="text-sm font-semibold text-primary shrink-0">{{ formatLineSubtotal(line) }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-line/40 text-xs text-secondary">
                        <span>{{ t('admin.ecommerce.orders.quantity') }}</span>
                        <span>× {{ line.quantity }}</span>
                    </div>
                </div>
                <div class="bg-surface-2 border border-line rounded-xl p-4 flex items-center justify-between">
                    <span class="font-semibold text-primary">{{ t('admin.ecommerce.orders.total') }}</span>
                    <span class="font-bold text-lg text-primary">{{ formattedTotal }}</span>
                </div>
            </div>

            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead class="bg-surface-2 border-b border-line">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.orders.product') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">SKU</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.orders.quantity') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.orders.subtotal') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="line in order.lines" :key="line.id">
                            <td class="px-4 py-3 text-primary">{{ line.title }}</td>
                            <td class="px-4 py-3 text-muted font-mono text-xs hidden md:table-cell">{{ line.sku ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-secondary">× {{ line.quantity }}</td>
                            <td class="px-4 py-3 text-right text-primary font-medium">{{ formatLineSubtotal(line) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-surface-2 border-t border-line">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right font-semibold text-primary">{{ t('admin.ecommerce.orders.total') }}</td>
                            <td class="px-4 py-3 text-right font-bold text-lg text-primary">{{ formattedTotal }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div v-if="canManage" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <h3 class="text-sm font-semibold text-primary uppercase tracking-wide">{{ t('admin.ecommerce.orders.actions.title') }}</h3>
                <AppButton
                    v-for="transition in availableTransitions"
                    :key="transition.status"
                    :variant="transition.color === 'emerald' ? 'primary' : 'secondary'"
                    size="md"
                    class="w-full"
                    :loading="loading"
                    v-on:click="confirmTransition(transition)"
                >
                    <component :is="transition.icon" class="w-4 h-4" :stroke-width="2" />
                    {{ transition.label }}
                </AppButton>
                <AppButton
                    v-if="canCancel"
                    variant="danger"
                    size="md"
                    class="w-full"
                    :loading="loading"
                    v-on:click="confirmTransition({ status: 'cancelled', label: t('admin.ecommerce.orders.actions.cancel'), icon: Ban })"
                >
                    <Ban class="w-4 h-4" :stroke-width="2" />
                    {{ t('admin.ecommerce.orders.actions.cancel') }}
                </AppButton>
                <p v-if="!availableTransitions.length && !canCancel" class="text-xs text-muted text-center py-2">{{ t('admin.ecommerce.orders.actions.no_transitions') }}</p>
            </div>

            <div class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <h3 class="text-sm font-semibold text-primary uppercase tracking-wide flex items-center gap-2">
                    <Clock class="w-4 h-4" :stroke-width="2" />
                    {{ t('admin.ecommerce.orders.timeline') }}
                </h3>
                <ol v-if="activity.length" class="space-y-3">
                    <li v-for="entry in activity" :key="entry.id" class="flex gap-3">
                        <span class="w-2 h-2 rounded-full bg-accent-500 mt-1.5 shrink-0" />
                        <div class="min-w-0">
                            <p class="text-sm text-primary">{{ actionLabel(entry.action) }}</p>
                            <p class="text-xs text-muted">{{ formatDateTime(entry.createdAt) }}<span v-if="entry.userName"> · {{ entry.userName }}</span></p>
                        </div>
                    </li>
                </ol>
                <p v-else class="text-xs text-muted text-center py-2">{{ t('admin.ecommerce.orders.no_activity') }}</p>
            </div>
        </div>

        <AppModal :show="!!pendingTransition" max-width="sm" v-on:close="pendingTransition = null">
            <p class="text-sm text-primary">{{ t('admin.ecommerce.orders.actions.confirm', { label: pendingTransition?.label }) }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingTransition = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton :variant="pendingTransition?.status === 'cancelled' ? 'danger' : 'primary'" size="md" :loading="loading" v-on:click="applyTransition">{{ t('shared.common.confirm') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
