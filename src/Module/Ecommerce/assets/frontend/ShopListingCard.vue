<script setup>
import { useI18n } from "vue-i18n";
import AppImage from "@/shared/components/display/AppImage.vue";
import { formatMoney } from "@ecommerce/shared/formatMoney.js";
import { ImageOff } from "lucide-vue-next";

const { t } = useI18n();

defineProps({
    listing: { type: Object, required: true },
    productUrl: { type: String, required: true },
});
</script>

<template>
    <article class="bg-surface border border-line/60 rounded-xl overflow-hidden hover:border-accent transition-colors">
        <a :href="productUrl" class="block">
            <div class="aspect-square bg-surface-2 overflow-hidden">
                <AppImage
                    v-if="listing.displayImage"
                    :src="listing.displayImage.url"
                    :alt="listing.displayImage.alt || listing.displayTitle"
                    object-fit="cover"
                    loading="lazy"
                />
                <div v-else class="w-full h-full flex flex-col items-center justify-center gap-1 text-muted text-xs">
                    <ImageOff class="w-6 h-6 opacity-40" :stroke-width="1.5" />
                    {{ t('frontend.shop.no_image') }}
                </div>
            </div>
            <div class="p-4 space-y-2">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="text-lg font-semibold text-primary">{{ listing.displayTitle }}</h2>
                    <span
                        v-if="listing.product?.type === 'digital'"
                        class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-accent-500/15 text-accent-400"
                    >
                        {{ t('frontend.shop.type.digital') }}
                    </span>
                    <span
                        v-else-if="listing.product?.type === 'service'"
                        class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-500/15 text-violet-400"
                    >
                        {{ t('frontend.shop.type.service') }}
                    </span>
                </div>
                <p v-if="listing.product?.price !== null && listing.product?.price !== undefined" class="text-base font-bold text-accent">
                    {{ formatMoney(listing.product.price, listing.product.currencySymbol) }}
                </p>
                <p
                    v-if="listing.product?.stockTracked && !listing.product?.inStock"
                    class="text-xs text-rose-400 font-medium"
                >
                    {{ t('frontend.shop.out_of_stock') }}
                </p>
                <p
                    v-else-if="listing.product?.isLowStock"
                    class="text-xs text-amber-400"
                >
                    {{ t('frontend.shop.low_stock', { count: listing.product.stockQuantity }) }}
                </p>
            </div>
        </a>
    </article>
</template>
