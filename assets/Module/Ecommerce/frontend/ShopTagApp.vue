<script setup>
import { useI18n } from "vue-i18n";
import { useUrlPagination } from "@/shared/composables/nav/useUrlPagination.js";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import ShopListingCard from "@ecommerce/frontend/ShopListingCard.vue";

const { t } = useI18n();
const { goToPage } = useUrlPagination();

const props = defineProps({
    tag: { type: Object, required: true },
    otherTags: { type: Array, default: () => [] },
    listings: { type: Array, default: () => [] },
    page: { type: Number, default: 1 },
    totalPages: { type: Number, default: 1 },
    shopPath: { type: String, required: true },
    tagPathBase: { type: String, required: true },
    productPathBase: { type: String, required: true },
});

function productUrl(slug) {
    return `${props.productPathBase}/${slug}`;
}

function tagUrl(slug) {
    return `${props.tagPathBase}/${slug}`;
}
</script>

<template>
    <section>
        <nav class="text-sm text-muted mb-6 flex flex-wrap items-center gap-2" :aria-label="t('frontend.shop.tag_breadcrumb')">
            <a :href="shopPath" class="hover:text-primary transition-colors">{{ t('frontend.shop.title') }}</a>
            <span class="text-line">/</span>
            <span class="text-primary" aria-current="page">{{ t('frontend.shop.tag_breadcrumb') }}</span>
        </nav>

        <header class="mb-8 flex flex-wrap items-center gap-3">
            <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white"
                :style="{ backgroundColor: tag.color }"
            >{{ tag.name }}</span>
            <h1 class="text-3xl font-bold text-primary">{{ tag.name }}</h1>
        </header>

        <p v-if="tag.description" class="mb-8 text-secondary">{{ tag.description }}</p>

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

        <nav v-if="otherTags.length" class="mt-12 pt-8 border-t border-line/60" :aria-label="t('frontend.shop.tags')">
            <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide mb-4">{{ t('frontend.shop.tags') }}</h2>
            <ul class="flex flex-wrap gap-2">
                <li v-for="otherTag in otherTags" :key="otherTag.slug">
                    <a
                        :href="tagUrl(otherTag.slug)"
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white hover:opacity-90 transition-opacity"
                        :style="{ backgroundColor: otherTag.color }"
                    >{{ otherTag.name }}</a>
                </li>
            </ul>
        </nav>
    </section>
</template>
