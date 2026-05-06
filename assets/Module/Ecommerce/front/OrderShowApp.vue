<script setup>
import { useI18n } from "vue-i18n";
import AppLink from "@/shared/components/nav/AppLink.vue";
import { formatMoney } from "@ecommerce/utils/formatMoney.js";

const { t } = useI18n();

defineProps({
    order: { type: Object, required: true },
    locale: { type: String, default: "fr" },
    shopPath: { type: String, required: true },
});
</script>

<template>
    <section class="space-y-6">
        <div class="text-center space-y-2">
            <div class="inline-flex w-16 h-16 rounded-full bg-emerald-500/15 text-emerald-400 items-center justify-center text-3xl">✓</div>
            <h1 class="text-3xl font-bold">{{ t('frontend.order.thanks') }}</h1>
            <p class="text-muted">{{ t('frontend.order.confirmation_intro', { number: order.number }) }}</p>
        </div>

        <div class="bg-surface border border-line rounded-xl p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('frontend.order.number') }}</p>
                    <p class="font-mono text-primary">{{ order.number }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('frontend.order.status') }}</p>
                    <p class="text-emerald-400 font-medium">{{ t('frontend.order.status_' + order.status) }}</p>
                </div>
            </div>

            <table class="w-full text-sm border-t border-line">
                <thead>
                    <tr class="text-xs text-muted uppercase">
                        <th class="text-left py-2">{{ t('frontend.order.product') }}</th>
                        <th class="text-right py-2">{{ t('frontend.order.qty') }}</th>
                        <th class="text-right py-2">{{ t('frontend.order.subtotal') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="line in order.lines" :key="line.id">
                        <td class="py-3">
                            <p class="text-primary">{{ line.title }}</p>
                            <p class="text-xs font-mono text-muted">{{ line.reference }}</p>
                        </td>
                        <td class="py-3 text-right text-secondary">{{ line.quantity }}</td>
                        <td class="py-3 text-right text-primary tabular-nums">{{ formatMoney(line.subtotal, line.currencySymbol) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-line">
                        <td colspan="2" class="pt-3 text-right font-semibold">{{ t('frontend.cart.total') }}</td>
                        <td class="pt-3 text-right text-xl font-bold text-accent">{{ formatMoney(order.total, order.currencySymbol) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div v-if="order.requiresShipping" class="bg-surface border border-line rounded-xl p-6 space-y-2">
            <h2 class="font-semibold text-primary mb-2">{{ t('frontend.checkout.shipping') }}</h2>
            <p class="text-secondary text-sm">{{ order.name }}</p>
            <p class="text-secondary text-sm">{{ order.addressLine1 }}</p>
            <p v-if="order.addressLine2" class="text-secondary text-sm">{{ order.addressLine2 }}</p>
            <p class="text-secondary text-sm">{{ order.postalCode }} {{ order.city }} ({{ order.country }})</p>
            <p class="text-muted text-sm pt-2">{{ order.email }}</p>
        </div>

        <div class="text-center">
            <AppLink :href="shopPath" variant="muted" size="sm">
                ← {{ t('frontend.cart.continue_shopping') }}
            </AppLink>
        </div>
    </section>
</template>
