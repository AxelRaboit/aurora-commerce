<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { formatCurrency } from "@/shared/utils/format/formatPrice.js";
import { useOrderStatusManagement } from "@/Module/Ecommerce/backend/orders/composables/useOrderStatusManagement.js";
import { useOrderRefund } from "@/Module/Ecommerce/backend/orders/composables/useOrderRefund.js";
import { OrderStatus } from "@/Module/Ecommerce/utils/enums/orderStatus.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppCheckbox from "@/shared/components/form/AppCheckbox.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { Ban, Clock, Undo2, X, Check } from "lucide-vue-next";

const { t } = useI18n();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    order: { type: Object, required: true },
    activity: { type: Array, default: () => [] },
    backPath: { type: String, required: true },
    updateStatusPath: { type: String, required: true },
    refundPath: { type: String, default: "" },
    canManage: { type: Boolean, default: false },
});

const order = ref({ ...props.order });
const activity = ref([...props.activity]);

const statusBadge = (status) => ({
    [OrderStatus.Pending]: "amber",
    [OrderStatus.Paid]: "sky",
    [OrderStatus.Shipped]: "accent",
    [OrderStatus.Delivered]: "emerald",
    [OrderStatus.Cancelled]: "rose",
    [OrderStatus.Refunded]: "slate",
}[status] ?? "slate");

const formattedTotal = computed(() => formatCurrency(order.value.total, order.value.currency));
const formatLineSubtotal = (line) => formatCurrency(line.subtotal, order.value.currency);

const { loading, availableTransitions, canCancel, pendingTransition, confirmTransition, applyTransition, actionLabel } =
    useOrderStatusManagement(props.updateStatusPath, order, activity);

const refund = useOrderRefund(props.refundPath, order);
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
                    <AppBadge :color="statusBadge(order.status)" class="self-start">{{ t(`backend.ecommerce.orders.status.${order.status}`) }}</AppBadge>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm border-t border-line pt-4">
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.ecommerce.orders.customer') }}</dt>
                        <dd class="text-primary">{{ order.name }}</dd>
                        <dd class="text-secondary">{{ order.email }}</dd>
                    </div>
                    <div v-if="order.requiresShipping">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.ecommerce.orders.shipping') }}</dt>
                        <dd class="text-secondary">{{ order.addressLine1 }}<span v-if="order.addressLine2">, {{ order.addressLine2 }}</span></dd>
                        <dd class="text-secondary">{{ order.postalCode }} {{ order.city }} ({{ order.country }})</dd>
                    </div>
                    <div v-else>
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.ecommerce.orders.fulfillment') }}</dt>
                        <dd class="text-secondary">{{ t('backend.ecommerce.orders.no_shipping_required') }}</dd>
                    </div>
                    <div v-if="order.notes" class="sm:col-span-2">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.ecommerce.orders.notes') }}</dt>
                        <dd class="text-secondary whitespace-pre-wrap">{{ order.notes }}</dd>
                    </div>
                </dl>
            </div>

            <div class="sm:hidden space-y-2">
                <div v-for="line in order.lines" :key="line.id" class="bg-surface border border-line rounded-xl p-4 space-y-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary">{{ line.title }}</p>
                            <p v-if="line.reference" class="text-xs text-muted font-mono mt-0.5">{{ line.reference }}</p>
                        </div>
                        <span class="text-sm font-semibold text-primary shrink-0">{{ formatLineSubtotal(line) }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-line/40 text-xs text-secondary">
                        <span>{{ t('backend.ecommerce.orders.quantity') }}</span>
                        <span>× {{ line.quantity }}</span>
                    </div>
                </div>
                <div class="bg-surface-2 border border-line rounded-xl p-4 flex items-center justify-between">
                    <span class="font-semibold text-primary">{{ t('backend.ecommerce.orders.total') }}</span>
                    <span class="font-bold text-lg text-primary">{{ formattedTotal }}</span>
                </div>
            </div>

            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.orders.product') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.ecommerce.orders.reference') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.orders.quantity') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.orders.subtotal') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="line in order.lines" :key="line.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-4 py-3 text-primary">{{ line.title }}</td>
                            <td class="px-4 py-3 text-muted font-mono text-xs hidden md:table-cell">{{ line.reference ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-secondary">× {{ line.quantity }}</td>
                            <td class="px-4 py-3 text-right text-primary font-medium">{{ formatLineSubtotal(line) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-surface-2 border-t border-line">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right font-semibold text-primary">{{ t('backend.ecommerce.orders.total') }}</td>
                            <td class="px-4 py-3 text-right font-bold text-lg text-primary">{{ formattedTotal }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div v-if="canManage" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <h3 class="text-sm font-semibold text-primary uppercase tracking-wide">{{ t('backend.ecommerce.orders.actions.title') }}</h3>
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
                    v-on:click="confirmTransition({ status: OrderStatus.Cancelled, label: t('backend.ecommerce.orders.actions.cancel'), icon: Ban })"
                >
                    <Ban class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.ecommerce.orders.actions.cancel') }}
                </AppButton>
                <AppButton
                    v-if="order.isRefundable && refundPath"
                    variant="secondary"
                    size="md"
                    class="w-full"
                    v-on:click="refund.open"
                >
                    <Undo2 class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.ecommerce.orders.refund.action') }}
                </AppButton>
                <p v-if="!availableTransitions.length && !canCancel && !order.isRefundable" class="text-xs text-muted text-center py-2">{{ t('backend.ecommerce.orders.actions.no_transitions') }}</p>
            </div>

            <div class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <h3 class="text-sm font-semibold text-primary uppercase tracking-wide flex items-center gap-2">
                    <Clock class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.ecommerce.orders.timeline') }}
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
                <p v-else class="text-xs text-muted text-center py-2">{{ t('backend.ecommerce.orders.no_activity') }}</p>
            </div>
        </div>

        <AppModal :show="!!pendingTransition" max-width="sm" v-on:close="pendingTransition = null">
            <p class="text-sm text-primary">{{ t('backend.ecommerce.orders.actions.confirm', { label: pendingTransition?.label }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingTransition = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton :variant="pendingTransition?.status === 'cancelled' ? 'danger' : 'primary'" size="md" :loading="loading" v-on:click="applyTransition"><Check class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.confirm') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal :show="refund.showModal.value" max-width="sm" :title="t('backend.ecommerce.orders.refund.title')" v-on:close="refund.close">
            <p class="text-sm text-secondary mb-4">{{ t('backend.ecommerce.orders.refund.hint') }}</p>
            <AppCheckbox v-model="refund.isFullRefund.value" :label="t('backend.ecommerce.orders.refund.full', { total: formattedTotal })" class="mb-3" />
            <AppInput
                v-if="!refund.isFullRefund.value"
                v-model="refund.refundAmount.value"
                type="number"
                :label="t('backend.ecommerce.orders.refund.amount')"
                step="0.01"
                min="0.01"
                :max="order.total"
                :placeholder="String(order.total)"
            />
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="refund.close"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="refund.loading.value" v-on:click="refund.confirm"><Undo2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('backend.ecommerce.orders.refund.confirm') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
