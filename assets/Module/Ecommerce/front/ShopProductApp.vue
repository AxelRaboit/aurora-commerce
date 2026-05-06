<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import AppImage from "@/shared/components/display/AppImage.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppLink from "@/shared/components/nav/AppLink.vue";
import { formatMoney } from "@ecommerce/utils/formatMoney.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

const { t } = useI18n();

const props = defineProps({
    listing: { type: Object, required: true },
    locale: { type: String, default: "fr" },
    cartAddPath: { type: String, required: true },
    shopPath: { type: String, required: true },
});

const quantity = ref(1);
const adding = ref(false);

const product = computed(() => props.listing.product);
const isInStock = computed(() => !product.value.stockTracked || product.value.inStock);
const maxQuantity = computed(() => product.value.stockTracked ? product.value.stockQuantity : null);

async function addToCart() {
    adding.value = true;
    try {
        const res = await fetch(props.cartAddPath, {
            method: HttpMethod.Post,
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
            body: JSON.stringify({ listingId: props.listing.id, quantity: quantity.value }),
        });
        const data = await res.json();
        if (data.success) {
            toast.success(t("frontend.shop.added_to_cart"));
            document.dispatchEvent(new CustomEvent("cart:changed", { detail: { count: data.cart?.totalQuantity ?? 0 } }));
        } else {
            toast.error(data.error || t("shared.common.error"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        adding.value = false;
    }
}
</script>

<template>
    <article class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <div class="aspect-square bg-surface-2 rounded-xl overflow-hidden">
                <AppImage
                    v-if="listing.displayImage"
                    :src="listing.displayImage.url"
                    :alt="listing.displayImage.alt || listing.displayTitle"
                    object-fit="cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-muted">
                    {{ t('frontend.shop.no_image') }}
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div>
                <p class="text-xs font-mono text-muted">{{ product.reference }}</p>
                <h1 class="text-3xl font-bold text-primary mt-1">{{ listing.displayTitle }}</h1>
            </div>

            <p v-if="product.price !== null" class="text-2xl font-bold text-accent">
                {{ formatMoney(product.price, product.currencySymbol) }}
            </p>

            <template v-if="product.stockTracked">
                <p v-if="!product.inStock" class="inline-flex items-center px-3 py-1.5 rounded text-sm font-medium bg-rose-500/15 text-rose-400">
                    {{ t('frontend.shop.out_of_stock') }}
                </p>
                <p v-else-if="product.isLowStock" class="text-sm text-amber-400">
                    {{ t('frontend.shop.low_stock', { count: product.stockQuantity }) }}
                </p>
                <p v-else class="text-sm text-emerald-400">{{ t('frontend.shop.in_stock') }}</p>
            </template>

            <div v-if="isInStock" class="flex items-end gap-3">
                <div class="w-24">
                    <AppInput
                        v-model="quantity"
                        type="number"
                        min="1"
                        :max="maxQuantity ?? undefined"
                    />
                </div>
                <AppButton
                    variant="primary"
                    size="md"
                    class="flex-1"
                    :loading="adding"
                    v-on:click="addToCart"
                >
                    {{ t('frontend.shop.add_to_cart') }}
                </AppButton>
            </div>

            <div v-if="listing.marketingDescription" class="prose prose-invert max-w-none">
                <p class="whitespace-pre-line text-secondary">{{ listing.marketingDescription }}</p>
            </div>

            <AppLink :href="shopPath" variant="muted" size="sm" class="inline-block">
                ← {{ t('frontend.shop.back_to_list') }}
            </AppLink>
        </div>
    </article>
</template>
