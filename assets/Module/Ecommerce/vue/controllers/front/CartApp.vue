<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import AppImage from "@/shared/components/display/AppImage.vue";

const { t } = useI18n();

const props = defineProps({
    initialCart: { type: Object, required: true },
    locale: { type: String, default: "fr" },
    updatePath: { type: String, required: true },
    removePath: { type: String, required: true },
    shopPath: { type: String, required: true },
    checkoutPath: { type: String, required: true },
});

const cart = ref(props.initialCart);
const pendingUpdates = ref(new Set()); // tracks listingIds being synced
const updatingHeader = ref(false);

const items = computed(() => cart.value.items ?? []);
const isEmpty = computed(() => items.value.length === 0);

function formatMoney(amount, symbol) {
    return `${(amount ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${symbol ?? ""}`.trim();
}

async function postJSON(url, body) {
    const response = await fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        },
        body: JSON.stringify(body),
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
}

async function syncQuantity(listingId, quantity) {
    pendingUpdates.value.add(listingId);
    try {
        const data = await postJSON(props.updatePath, { listingId, quantity });
        if (data.ok) {
            cart.value = data.cart;
            broadcastCartChange(data.cart.totalQuantity);
        }
    } catch {
        // silently fail; user will see stale state and can retry
    } finally {
        pendingUpdates.value.delete(listingId);
    }
}

const debouncedSync = useDebounce(syncQuantity, 400);

function onQuantityChange(item, newValue) {
    const qty = Math.max(1, parseInt(newValue, 10) || 1);
    item.quantity = qty;
    item.subtotal = item.unitPrice * qty;
    cart.value.totalQuantity = items.value.reduce((sum, it) => sum + it.quantity, 0);
    cart.value.total = items.value.reduce((sum, it) => sum + it.subtotal, 0);
    debouncedSync(item.listingId, qty);
}

function increment(item) {
    onQuantityChange(item, item.quantity + 1);
}

function decrement(item) {
    if (item.quantity <= 1) return;
    onQuantityChange(item, item.quantity - 1);
}

async function removeItem(item) {
    pendingUpdates.value.add(item.listingId);
    try {
        const data = await postJSON(props.removePath, { listingId: item.listingId });
        if (data.ok) {
            cart.value = data.cart;
            broadcastCartChange(data.cart.totalQuantity);
        }
    } finally {
        pendingUpdates.value.delete(item.listingId);
    }
}

function broadcastCartChange(count) {
    updatingHeader.value = true;
    document.dispatchEvent(new CustomEvent("cart:changed", { detail: { count } }));
    setTimeout(() => { updatingHeader.value = false; }, 200);
}
</script>

<template>
    <section>
        <h1 class="text-3xl font-bold mb-6">{{ t('front.cart.title') }}</h1>

        <div v-if="isEmpty" class="bg-surface border border-line rounded-xl p-8 text-center space-y-4">
            <p class="text-muted">{{ t('front.cart.empty') }}</p>
            <a :href="shopPath" class="inline-block px-4 py-2 bg-accent text-white rounded-md hover:opacity-90 transition-opacity">
                {{ t('front.cart.browse_shop') }}
            </a>
        </div>

        <div v-else class="space-y-4">
            <div v-for="item in items" :key="item.listingId" class="bg-surface border border-line rounded-xl p-4 flex items-center gap-4 transition-opacity" :class="{ 'opacity-60': pendingUpdates.has(item.listingId) }">
                <div v-if="item.imageUrl" class="w-20 h-20 bg-surface-2 rounded-lg overflow-hidden shrink-0">
                    <AppImage :src="item.imageUrl" :alt="item.title" object-fit="cover" />
                </div>
                <div v-else class="w-20 h-20 bg-surface-2 rounded-lg shrink-0" />

                <div class="flex-1 min-w-0">
                    <p class="font-medium text-primary truncate">{{ item.title }}</p>
                    <p class="text-xs font-mono text-muted">{{ item.sku }}</p>
                    <p class="text-sm text-secondary mt-1">{{ formatMoney(item.unitPrice, item.currencySymbol) }}</p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <div class="inline-flex items-center rounded-md border border-line bg-surface-2 overflow-hidden">
                        <button type="button" class="px-3 py-1.5 text-sm hover:bg-surface-3 transition-colors disabled:opacity-30 disabled:cursor-not-allowed" :disabled="item.quantity <= 1" v-on:click="decrement(item)">
                            −
                        </button>
                        <input
                            type="number"
                            :value="item.quantity"
                            min="1"
                            class="w-12 text-center bg-transparent text-sm focus:outline-none tabular-nums"
                            v-on:change="onQuantityChange(item, $event.target.value)"
                        >
                        <button type="button" class="px-3 py-1.5 text-sm hover:bg-surface-3 transition-colors" v-on:click="increment(item)">
                            +
                        </button>
                    </div>
                    <p class="text-base font-bold text-accent tabular-nums w-24 text-right">{{ formatMoney(item.subtotal, item.currencySymbol) }}</p>
                    <button type="button" class="text-rose-400 hover:text-rose-500 text-xl leading-none px-1" :aria-label="t('shared.common.delete')" v-on:click="removeItem(item)">
                        ×
                    </button>
                </div>
            </div>

            <div class="bg-surface border border-line rounded-xl p-6 flex items-center justify-between">
                <span class="text-lg font-semibold text-primary">{{ t('front.cart.total') }}</span>
                <span class="text-2xl font-bold text-accent tabular-nums transition-all">{{ formatMoney(cart.total, cart.currencySymbol) }}</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-between gap-3">
                <a :href="shopPath" class="text-sm text-muted hover:text-primary self-center">
                    ← {{ t('front.cart.continue_shopping') }}
                </a>
                <a :href="checkoutPath" class="inline-block px-6 py-3 bg-accent text-white font-medium rounded-md hover:opacity-90 transition-opacity text-center">
                    {{ t('front.cart.checkout') }}
                </a>
            </div>
        </div>
    </section>
</template>
