<script setup>
import { useI18n } from "vue-i18n";
import AppImage from "@/shared/components/display/AppImage.vue";
import { formatMoney } from "@ecommerce/utils/formatMoney.js";

const { t } = useI18n();

const props = defineProps({
    listings: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({ page: 1, totalPages: 1 }) },
    locale: { type: String, default: "fr" },
    productPathBase: { type: String, required: true },
    indexPath: { type: String, required: true },
});

function productUrl(slug) {
    return `${props.productPathBase}/${slug}`;
}

function pageUrl(n) {
    const url = new URL(props.indexPath, window.location.origin);
    if (n > 1) url.searchParams.set("page", String(n));
    return url.pathname + url.search;
}
</script>

<template>
    <section>
        <h1 class="text-3xl font-bold mb-6">{{ t('front.shop.title') }}</h1>

        <p v-if="!listings.length" class="text-muted">{{ t('front.shop.empty') }}</p>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <article
                v-for="listing in listings"
                :key="listing.id"
                class="bg-surface border border-line/60 rounded-xl overflow-hidden hover:border-accent transition-colors"
            >
                <a :href="productUrl(listing.slug)" class="block">
                    <div class="aspect-square bg-surface-2 overflow-hidden">
                        <AppImage
                            v-if="listing.displayImage"
                            :src="listing.displayImage.url"
                            :alt="listing.displayImage.alt || listing.displayTitle"
                            object-fit="cover"
                            loading="lazy"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-muted text-xs">{{ t('front.shop.no_image') }}</div>
                    </div>
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="text-lg font-semibold text-primary">{{ listing.displayTitle }}</h2>
                            <span v-if="listing.product.type === 'digital'" class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-accent-500/15 text-accent-400">
                                {{ t('front.shop.type.digital') }}
                            </span>
                            <span v-else-if="listing.product.type === 'service'" class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-500/15 text-violet-400">
                                {{ t('front.shop.type.service') }}
                            </span>
                        </div>
                        <p v-if="listing.product.price !== null" class="text-base font-bold text-accent">
                            {{ formatMoney(listing.product.price, listing.product.currencySymbol) }}
                        </p>
                        <p v-if="listing.product.stockTracked && !listing.product.inStock" class="text-xs text-rose-400 font-medium">
                            {{ t('front.shop.out_of_stock') }}
                        </p>
                        <p v-else-if="listing.product.isLowStock" class="text-xs text-amber-400">
                            {{ t('front.shop.low_stock', { count: listing.product.stockQuantity }) }}
                        </p>
                    </div>
                </a>
            </article>
        </div>

        <nav v-if="pagination.totalPages > 1" class="flex items-center justify-center gap-1 mt-8">
            <a
                v-for="n in pagination.totalPages"
                :key="n"
                :href="pageUrl(n)"
                class="w-9 h-9 flex items-center justify-center rounded-md text-sm font-medium transition-colors"
                :class="n === pagination.page ? 'bg-accent text-white' : 'bg-surface text-secondary hover:text-primary'"
            >
                {{ n }}
            </a>
        </nav>
    </section>
</template>
