import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";

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

    function pickTranslation(category) {
        const translations = category.translations ?? {};
        return (
            translations[locale.value]
            ?? translations.en
            ?? Object.values(translations)[0]
            ?? null
        );
    }

    const flatCategories = computed(() => {
        return availableCategories.value.map((category) => {
            const translation = pickTranslation(category);
            const name = translation?.name ?? `#${category.id}`;
            const indent = "— ".repeat(category.depth);
            return {
                id: category.id,
                label: `${indent}${name}`,
                depth: category.depth,
                name,
            };
        });
    });

    onMounted(loadCategories);

    return { availableCategories, flatCategories, loadCategories };
}
