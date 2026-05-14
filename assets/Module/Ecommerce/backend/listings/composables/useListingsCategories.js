import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { pickTranslation } from "@/shared/utils/i18n/pickTranslation.js";

export function useListingsCategories(categoriesPath) {
    const { locale } = useI18n();
    const availableCategories = ref([]);

    async function loadCategories() {
        const response = await fetch(categoriesPath, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        const data = await response.json();
        if (data.success) availableCategories.value = data.items;
    }

    const flatCategories = computed(() => {
        return availableCategories.value.map((category) => {
            const translation = pickTranslation(category, locale.value);
            const name = translation?.name ?? `#${category.id}`;
            return {
                id: category.id,
                label: name,
                depth: category.depth,
                name,
            };
        });
    });

    onMounted(loadCategories);

    return { availableCategories, flatCategories, loadCategories };
}
