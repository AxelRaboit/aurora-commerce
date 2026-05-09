<script setup>
import { useI18n } from "vue-i18n";
import { useCart } from "@ecommerce/frontend/composables/useCart.js";
import AppImage from "@/shared/components/display/AppImage.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppLink from "@/shared/components/nav/AppLink.vue";
import { formatMoney } from "@ecommerce/shared/formatMoney.js";

const { t } = useI18n();

const props = defineProps({
    initialCart: { type: Object, required: true },
    locale: { type: String, default: "fr" },
    updatePath: { type: String, required: true },
    removePath: { type: String, required: true },
    shopPath: { type: String, required: true },
    checkoutPath: { type: String, required: true },
});

const { cart, pendingUpdates, updatingHeader, items, isEmpty, onQuantityChange, increment, decrement, removeItem } = useCart(props);
</script>

<template>
    <section>
        <h1 class="text-3xl font-bold mb-6">{{ t('frontend.cart.title') }}</h1>

        <div v-if="isEmpty" class="bg-surface border border-line rounded-xl p-8 text-center space-y-4">
            <p class="text-muted">{{ t('frontend.cart.empty') }}</p>
            <AppButton :href="shopPath" variant="accent">
                {{ t('frontend.cart.browse_shop') }}
            </AppButton>
        </div>

        <div v-else class="space-y-4">
            <div v-for="item in items" :key="item.listingId" class="bg-surface border border-line rounded-xl p-4 flex items-center gap-4 transition-opacity" :class="{ 'opacity-60': pendingUpdates.has(item.listingId) }">
                <div v-if="item.imageUrl" class="w-20 h-20 bg-surface-2 rounded-lg overflow-hidden shrink-0">
                    <AppImage :src="item.imageUrl" :alt="item.title" object-fit="cover" />
                </div>
                <div v-else class="w-20 h-20 bg-surface-2 rounded-lg shrink-0" />

                <div class="flex-1 min-w-0">
                    <p class="font-medium text-primary truncate">{{ item.title }}</p>
                    <p class="text-xs font-mono text-muted">{{ item.reference }}</p>
                    <p class="text-sm text-secondary mt-1">{{ formatMoney(item.unitPrice, item.currencySymbol) }}</p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <div class="inline-flex items-center rounded-md border border-line bg-surface-2 overflow-hidden">
                        <AppIconButton size="compact" class="px-3 py-1.5" :disabled="item.quantity <= 1" v-on:click="decrement(item)">−</AppIconButton>
                        <input
                            type="number"
                            :value="item.quantity"
                            min="1"
                            class="w-12 text-center bg-transparent text-sm focus:outline-none tabular-nums"
                            v-on:change="onQuantityChange(item, $event.target.value)"
                        >
                        <AppIconButton size="compact" class="px-3 py-1.5" v-on:click="increment(item)">+</AppIconButton>
                    </div>
                    <p class="text-base font-bold text-accent tabular-nums w-24 text-right">{{ formatMoney(item.subtotal, item.currencySymbol) }}</p>
                    <AppIconButton color="rose" :aria-label="t('shared.common.delete')" v-on:click="removeItem(item)">
                        <span class="text-xl leading-none">×</span>
                    </AppIconButton>
                </div>
            </div>

            <div class="bg-surface border border-line rounded-xl p-6 flex items-center justify-between">
                <span class="text-lg font-semibold text-primary">{{ t('frontend.cart.total') }}</span>
                <span class="text-2xl font-bold text-accent tabular-nums transition-all">{{ formatMoney(cart.total, cart.currencySymbol) }}</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-between gap-3">
                <AppLink :href="shopPath" variant="muted" size="sm" class="self-center">
                    ← {{ t('frontend.cart.continue_shopping') }}
                </AppLink>
                <AppButton :href="checkoutPath" variant="accent" size="lg">
                    {{ t('frontend.cart.checkout') }}
                </AppButton>
            </div>
        </div>
    </section>
</template>
