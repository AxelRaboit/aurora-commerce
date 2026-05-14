<script setup>
import { useI18n } from "vue-i18n";
import { useUrlPagination } from "@/shared/composables/nav/useUrlPagination.js";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import ShopListingCard from "@ecommerce/frontend/ShopListingCard.vue";

const { t } = useI18n();
const { goToPage } = useUrlPagination();

const props = defineProps({
    category: { type: Object, required: true },
    breadcrumb: { type: Array, default: () => [] },
    listings: { type: Array, default: () => [] },
    page: { type: Number, default: 1 },
    totalPages: { type: Number, default: 1 },
    shopPath: { type: String, required: true },
    categoryPathBase: { type: String, required: true },
    productPathBase: { type: String, required: true },
});

function productUrl(slug) {
    return `${props.productPathBase}/${slug}`;
}

function categoryUrl(slug) {
    return `${props.categoryPathBase}/${slug}`;
}
</script>

<template>
    <section>
        <nav class="text-sm text-muted mb-6 flex flex-wrap items-center gap-2" :aria-label="t('frontend.shop.category_breadcrumb')">
            <a :href="shopPath" class="hover:text-primary transition-colors">{{ t('frontend.shop.title') }}</a>
            <template v-for="crumb in breadcrumb" :key="crumb.slug">
                <span class="text-line">/</span>
                <a :href="categoryUrl(crumb.slug)" class="hover:text-primary transition-colors">{{ crumb.name }}</a>
            </template>
            <span class="text-line">/</span>
            <span class="text-primary" aria-current="page">{{ category.name }}</span>
        </nav>

        <header class="mb-8">
            <h1 class="text-3xl font-bold text-primary">{{ category.name }}</h1>
            <p v-if="category.description" class="mt-4 text-secondary">{{ category.description }}</p>
        </header>

        <AppNoData v-if="!listings.length" :message="t('frontend.shop.empty')" />

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <ShopListingCard
                v-for="listing in listings"
                :key="listing.id"
                :listing="listing"
                :product-url="productUrl(listing.slug)"
            />
        </div>

        <div v-if="listings.length && totalPages > 1" class="mt-8">
            <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />
        </div>
    </section>
</template>
