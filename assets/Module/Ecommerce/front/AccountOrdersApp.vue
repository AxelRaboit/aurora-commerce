<script setup>
import { useI18n } from "vue-i18n";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import AppLink from "@/shared/components/nav/AppLink.vue";
import { formatMoney } from "@ecommerce/utils/formatMoney.js";

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const props = defineProps({
    orders: { type: Array, default: () => [] },
    locale: { type: String, default: "fr" },
    accountPath: { type: String, required: true },
    shopPath: { type: String, required: true },
    orderPathBase: { type: String, required: true },
});

function orderUrl(token) {
    return `${props.orderPathBase}/${token}`;
}
</script>

<template>
    <section class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold">{{ t('frontend.account.orders') }}</h1>
            <AppLink :href="accountPath" variant="muted" size="sm">
                ← {{ t('frontend.account.back') }}
            </AppLink>
        </div>

        <div v-if="!orders.length" class="bg-surface border border-line rounded-xl p-8 text-center space-y-3">
            <p class="text-muted">{{ t('frontend.account.no_orders') }}</p>
            <AppLink :href="shopPath" variant="front-accent" class="inline-block">{{ t('frontend.cart.browse_shop') }}</AppLink>
        </div>

        <div v-else class="bg-surface border border-line rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 border-b border-line">
                    <tr class="text-xs text-muted uppercase">
                        <th class="px-4 py-3 text-left">{{ t('frontend.order.number') }}</th>
                        <th class="px-4 py-3 text-left hidden sm:table-cell">{{ t('frontend.order.date') }}</th>
                        <th class="px-4 py-3 text-left">{{ t('frontend.order.status') }}</th>
                        <th class="px-4 py-3 text-right">{{ t('frontend.cart.total') }}</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="order in orders" :key="order.id" class="hover:bg-surface-2/50 transition-colors">
                        <td class="px-4 py-3 font-mono text-primary">{{ order.number }}</td>
                        <td class="px-4 py-3 text-secondary hidden sm:table-cell">{{ formatDateShort(order.createdAt) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-surface-2 text-secondary">
                                {{ t('frontend.order.status_' + order.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-primary tabular-nums font-medium">
                            {{ formatMoney(order.total, order.currencySymbol) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <AppLink :href="orderUrl(order.token)" variant="front-accent" size="sm">
                                {{ t('frontend.order.view') }}
                            </AppLink>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
